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
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Commons\Support\CorsPolicy;
use Charcoal\Http\Commons\Support\HttpHelper;
use Charcoal\Http\Server\Config\RequestConstraints;
use Charcoal\Http\Server\Config\VirtualHost;
use Charcoal\Http\Server\Contracts\Controllers\InvokableControllerInterface;
use Charcoal\Http\Server\Enums\RequestError;
use Charcoal\Http\Server\Exceptions\PreFlightTerminateException;
use Charcoal\Http\Server\Exceptions\RequestContextException;
use Charcoal\Http\Server\Middleware\MiddlewareFacade;
use Charcoal\Http\Server\Request\Controller\RequestFacade;
use Charcoal\Http\Server\Routing\Snapshot\RouteControllerBinding;
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
     * @throws RequestContextException
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
            throw new RequestContextException(match ($e->getCode()) {
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
            throw new RequestContextException(match (true) {
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
     * @throws RequestContextException
     * @throws PreFlightTerminateException
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public function preFlightCorsControl(
        CorsPolicy             $corsPolicy,
        RouteControllerBinding $routeController,
        string                 $entrypoint,
        array                  $pathParams
    ): void
    {
        $this->pathParams = $pathParams;
        $this->routeController = $routeController;
        $this->controllerEp = $entrypoint;

        // Cors policy applicable if Origin header is present
        $origin = $this->request->headers->get("Origin");
        if ($origin) {
            // Validate Origin Header
            if (!HttpHelper::isValidOrigin($origin)) {
                throw new RequestContextException(RequestError::BadOriginHeader, null);
            }

            /** @see PreFlightTerminateException */
            $this->middleware->optionsMethodHandler($origin, $corsPolicy, $this->responseHeaders);

            // Continuing?
            if (!$this->responseHeaders->has("Access-Control-Allow-Origin") ||
                $this->responseHeaders->get("Vary") !== "Origin") {
                throw new RequestContextException(RequestError::BadOriginHeader, null);
            }
        }

        // Initiate Output Buffer
        $this->output = new WritablePayload();
        // Todo: $this->input = UnsafePayload from Decoder Pipeline
    }

    public function executeController(): void
    {
        try {
            $requestFacade = new RequestFacade($this);
            $controller = new $this->routeController->controller->classname($this);
            if ($controller instanceof InvokableControllerInterface) {
                $controller($requestFacade);
                return;
            }

            call_user_func_array([$controller, $this->controllerEp], [$requestFacade]);
        } catch (\Throwable $e) {

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