<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request;

use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Commons\Support\CorsPolicy;
use Charcoal\Http\Commons\Support\HttpHelper;
use Charcoal\Http\Server\Config\RequestConstraints;
use Charcoal\Http\Server\Config\VirtualHost;
use Charcoal\Http\Server\Contracts\Controllers\Hooks\AfterEntrypointCallback;
use Charcoal\Http\Server\Contracts\Controllers\Hooks\BeforeEntrypointCallback;
use Charcoal\Http\Server\Contracts\Controllers\InvokableControllerInterface;
use Charcoal\Http\Server\Enums\ControllerError;
use Charcoal\Http\Server\Enums\RequestError;
use Charcoal\Http\Server\Exceptions\Controllers\ValidationErrorException;
use Charcoal\Http\Server\Exceptions\Controllers\ValidationException;
use Charcoal\Http\Server\Exceptions\PreFlightTerminateException;
use Charcoal\Http\Server\Exceptions\RequestGatewayException;
use Charcoal\Http\Server\Middleware\MiddlewareFacade;
use Charcoal\Http\Server\Request\Controller\RequestFacade;
use Charcoal\Http\Server\Routing\Router;
use Charcoal\Http\Server\Routing\Snapshot\RouteControllerBinding;
use Charcoal\Http\Server\Routing\Snapshot\RouteSnapshot;
use Charcoal\Http\TrustProxy\Result\TrustGatewayResult;

/**
 * Represents the context of an HTTP request, encompassing details such as
 * request headers, payload, trust gateway information, and internal buffer states.
 * This class is designed to facilitate HTTP request handling, processing pipelines,
 * and error management during runtime.
 */
final readonly class RequestGateway
{
    use NoDumpTrait;
    use NotSerializableTrait;
    use NotCloneableTrait;

    public ?string $requestId;
    public ServerRequest $request;
    public RouteControllerBinding $routeController;
    public ?ContentType $contentType;
    public int $contentLength;
    public string $controllerEp;
    public array $pathParams;

    private VirtualHost $host;
    private TrustGatewayResult $trustProxy;

    public UnsafePayload $input;
    public WritablePayload $output;
    public ?CacheControlDirectives $cacheControl;

    /**
     * @throws RequestGatewayException
     */
    public function __construct(
        public string              $uuid,
        public Headers             $responseHeaders,
        ServerRequest              $request,
        private RequestConstraints $constraints,
        private MiddlewareFacade   $middleware,
    )
    {
        // URL Validation
        try {
            $this->middleware->urlValidationPipeline($request->url, $this->constraints->maxUriBytes);
        } catch (\Exception $e) {
            throw new RequestGatewayException(match ($e->getCode()) {
                414 => RequestError::BadUrlLength,
                default => RequestError::BadUrlEncoding
            }, $e);
        }

        // Headers Validation and Normalization
        try {
            $headers = $this->middleware->headerValidationPipeline(
                $request->headers,
                $this->constraints->maxHeaders,
                $this->constraints->maxHeaderLength,
                $this->constraints->headerKeyValidation
            );
        } catch (\Exception $e) {
            throw new RequestGatewayException(match (true) {
                $e instanceof \OutOfRangeException => RequestError::HeadersCountCap,
                $e instanceof \InvalidArgumentException => RequestError::BadHeaderName,
                $e instanceof \LengthException => RequestError::HeaderLength,
                $e instanceof \DomainException => RequestError::BadHeaderValue,
                default => RequestError::BadHeaders,
            }, $e);
        }

        // Set the normalized request headers
        $this->request = $request->withHeaders($headers);
    }

    /**
     * @param VirtualHost $host
     * @param TrustGatewayResult $trustProxy
     * @return void
     */
    public function accepted(VirtualHost $host, TrustGatewayResult $trustProxy): void
    {
        // Set the host and trust proxy instances
        $this->host = $host;
        $this->trustProxy = $trustProxy;

        // Negotiate X-Request-ID and Content-Type
        $requestId = $this->request->headers->get("X-Request-ID");
        if ($requestId) {
            if (strlen($requestId) === 36 && str_contains($requestId, "-")) {
                $requestId = str_replace("-", "", $requestId);
            }

            if (strlen($requestId) === 32 && ctype_xdigit($requestId)) {
                if ($requestId === str_repeat("0", 32)) {
                    $requestId = null;
                }
            }

            // Override our randomly generated one with the one that came with request
            $this->responseHeaders->set("X-Request-ID", $requestId);
        }

        $this->requestId = $this->responseHeaders->get("X-Request-ID");

        // Content-Type and Content-Length
        $this->contentType = ContentType::find($this->request->headers->get("Content-Type") ?? "");
        $this->contentLength = (int)$this->request->headers->get("Content-Length");
    }

    /**
     * @throws RequestGatewayException
     * @throws PreFlightTerminateException
     */
    public function preFlightControl(
        Router                 $router,
        CorsPolicy             $corsPolicy,
        RouteSnapshot          $route,
        RouteControllerBinding $controller,
        array                  $pathParams
    ): void
    {
        $this->routeController = $controller;
        $this->pathParams = $pathParams;

        // Cors policy applicable if Origin header is present
        $isPreFlight = $this->request->method === HttpMethod::OPTIONS;
        $origin = $this->request->headers->get("Origin");
        if ($origin) {
            // Validate Origin Header
            if (!HttpHelper::isValidOrigin($origin)) {
                $this->responseHeaders->set("Vary", "Origin");
                throw new RequestGatewayException(RequestError::BadOriginHeader, null);
            }

            match ($corsPolicy->enforce) {
                false => $this->responseHeaders->set("Access-Control-Allow-Origin", "*"),
                true => $this->validateOrigin($origin, $corsPolicy, $this->request->method)
            };
        }

        // Resolve Entrypoint
        $entryPoint = $controller->matchEntryPoint($this->request->method);
        if (!$entryPoint) {
            $allowed = implode(", ", $router->getAllowedMethodsFor($route));
            if ($origin && $isPreFlight) {
                $this->defaultPreFlightRequestHandler($allowed, $corsPolicy);
            }

            $this->responseHeaders->set("Allow", $allowed);
            throw new RequestGatewayException(RequestError::MethodNotAllowed, null);
        }

        $this->controllerEp = $entryPoint;

        // Initiate Output Buffer
        $this->output = new WritablePayload();
        // Todo: $this->input = UnsafePayload from Decoder Pipeline
    }

    /**
     * @throws PreFlightTerminateException
     */
    private function defaultPreFlightRequestHandler(string $methods, CorsPolicy $corsPolicy): void
    {
        $this->responseHeaders->set("Access-Control-Allow-Methods", $methods)
            ->set("Access-Control-Allow-Headers", $corsPolicy->allow)
            ->set("Access-Control-Max-Age", strval($corsPolicy->maxAge))
            ->set("Cache-Control", "no-store");

        if ($corsPolicy->enforce) {
            $this->responseHeaders->set("Vary",
                "Origin, Access-Control-Request-Method, Access-Control-Request-Headers");
        } else {
            $this->responseHeaders->set("Vary",
                "Access-Control-Request-Method, Access-Control-Request-Headers");
        }

        throw new PreFlightTerminateException();
    }

    /**
     * @throws RequestGatewayException
     */
    private function validateOrigin(string $origin, CorsPolicy $corsPolicy, HttpMethod $method): void
    {
        // Validate Origin against the approved
        if (!in_array(strtolower($origin), $corsPolicy->origins, true)) {
            $this->responseHeaders->set("Vary", "Origin");
            throw new RequestGatewayException(RequestError::CorsOriginNotAllowed, null);
        }

        $this->responseHeaders->set("Access-Control-Allow-Origin", $origin)
            ->set("Access-Control-Expose-Headers", $corsPolicy->expose);

        if ($corsPolicy->withCredentials) {
            $this->responseHeaders->set("Access-Control-Allow-Credentials", "true");
        }

        if ($method !== HttpMethod::OPTIONS) {
            $this->responseHeaders->set("Vary", "Origin");
        }
    }

    /**
     * @return void
     * @throws RequestGatewayException
     */
    public function executeController(): void
    {
        $requestFacade = new RequestFacade($this);
        $controllerContext = $this->routeController->controller;

        try {
            $controller = new $controllerContext->classname($this);
            if ($controller instanceof BeforeEntrypointCallback) {
                $controller->beforeEntrypointCallback($requestFacade);
            }

            // Single Entrypoint?
            if (count($controllerContext->entryPoints) === 1) {
                $requestFacade->enforceRequiredParams();
            }

            if ($controller instanceof InvokableControllerInterface) {
                $controller($requestFacade);
            } else {
                call_user_func_array([$controller, $this->controllerEp], [$requestFacade]);
            }

            if ($controller instanceof AfterEntrypointCallback) {
                $controller->afterEntrypointCallback($requestFacade);
            }
        } catch (\Exception $e) {
            if ($e instanceof ValidationErrorException) {
                $e->setContextMessage($requestFacade);
            }

            if ($e instanceof ValidationException) {
                throw new RequestGatewayException(ControllerError::ValidationException, $e);
            }

            throw new RequestGatewayException(ControllerError::ExecutionFlow, $e);
        }
    }

    /**
     * @param CacheControlDirectives $cacheControl
     * @return void
     */
    public function setCacheControl(CacheControlDirectives $cacheControl): void
    {
        if (isset($this->cacheControl)) {
            throw new \BadMethodCallException("Duplicate cache control directives");
        }

        $this->cacheControl = $cacheControl;
    }
}