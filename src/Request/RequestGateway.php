<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request;

use Charcoal\Base\Arrays\ArrayHelper;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Base\Objects\Traits\NoDumpTrait;
use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Buffers\Buffer;
use Charcoal\Charsets\Support\AsciiHelper;
use Charcoal\Contracts\Sapi\Exceptions\TranslatedExceptionInterface;
use Charcoal\Contracts\Sapi\Exceptions\ValidationExceptionInterface;
use Charcoal\Http\Commons\Body\PayloadImmutable;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Support\CorsPolicy;
use Charcoal\Http\Commons\Support\HttpHelper;
use Charcoal\Http\Server\Config\RequestConstraints;
use Charcoal\Http\Server\Config\VirtualHost;
use Charcoal\Http\Server\Contracts\Controllers\Hooks\AfterEntrypointCallback;
use Charcoal\Http\Server\Contracts\Controllers\Hooks\BeforeEntrypointCallback;
use Charcoal\Http\Server\Contracts\Controllers\InvokableControllerInterface;
use Charcoal\Http\Server\Contracts\Request\SuccessResponseInterface;
use Charcoal\Http\Server\Enums\ContentEncoding;
use Charcoal\Http\Server\Enums\ControllerAttribute;
use Charcoal\Http\Server\Enums\ControllerError;
use Charcoal\Http\Server\Enums\RequestConstraint;
use Charcoal\Http\Server\Enums\RequestError;
use Charcoal\Http\Server\Enums\TransferEncoding;
use Charcoal\Http\Server\Exceptions\Controllers\ValidationTranslatedException;
use Charcoal\Http\Server\Exceptions\Internal\PreFlightTerminateException;
use Charcoal\Http\Server\Exceptions\Internal\RequestGatewayException;
use Charcoal\Http\Server\Exceptions\Internal\Response\ResponseFinalizedInterrupt;
use Charcoal\Http\Server\HttpServer;
use Charcoal\Http\Server\Middleware\MiddlewareFacade;
use Charcoal\Http\Server\Request\Bags\QueryParams;
use Charcoal\Http\Server\Request\Controller\RequestFacade;
use Charcoal\Http\Server\Request\Controller\ResponseFacade;
use Charcoal\Http\Server\Request\Controller\ServerFacade;
use Charcoal\Http\Server\Request\Files\FileUpload;
use Charcoal\Http\Server\Request\Result\Response\EncodedBufferResponse;
use Charcoal\Http\Server\Request\Result\Response\EncodedResponseBody;
use Charcoal\Http\Server\Request\Result\Response\NoContentResponse;
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

    public ServerRequest $request;
    public ServerFacade $serverFacade;
    public RequestFacade $requestFacade;
    public RouteControllerBinding $routeController;
    public string $controllerEp;
    public ResponseFacade $response;
    private ?SuccessResponseInterface $finalizedResponse;

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
     * @throws RequestGatewayException
     */
    public function accepted(VirtualHost $host, TrustGatewayResult $trustProxy): void
    {
        // Set the host and trust proxy instances
        $this->serverFacade = new ServerFacade($host, $trustProxy);

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

        // Content Length
        $contentLength = $this->request->headers->get("Content-Length");
        if ($contentLength && !ctype_digit($contentLength)) {
            throw new RequestGatewayException(RequestError::BadContentLength, null);
        }

        $contentLength = (int)$contentLength;
        if ($contentLength < 0) {
            throw new RequestGatewayException(RequestError::BadContentLength, null);
        }

        // Initialize Request Facade
        $this->requestFacade = new RequestFacade(
            $this->responseHeaders->get("X-Request-ID"),
            $this->serverFacade->proxy->clientIp,
            $this->request->method,
            $this->request->headers,
            new QueryParams(explode("#", explode("?",
                $this->request->url->complete, 2)[1] ?? "", 2)[0]),
            ContentType::find($this->request->headers->get("Content-Type") ?? ""),
            $contentLength,
            TransferEncoding::find($this->request->headers->get("Transfer-Encoding")),
            ContentEncoding::find($this->request->headers->get("Content-Encoding"))
        );
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
        $this->requestFacade->setPathParams($pathParams);

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

            // Handle preflight requests before entrypoint resolution
            if ($isPreFlight) {
                $allowed = implode(", ", $router->getAllowedMethodsFor($route));
                $this->defaultPreFlightRequestHandler($allowed, $corsPolicy);
            }
        }

        // Resolve Entrypoint
        $entryPoint = $controller->matchEntryPoint($this->request->method);
        if (!$entryPoint) {
            $allowed = implode(", ", $router->getAllowedMethodsFor($route));
            $this->responseHeaders->set("Allow", $allowed);
            throw new RequestGatewayException(RequestError::MethodNotAllowed, null);
        }

        $this->controllerEp = $entryPoint;

        // Initiate Response Facade
        $this->response = new ResponseFacade();
    }

    /**
     * @throws PreFlightTerminateException
     */
    private function defaultPreFlightRequestHandler(string $methods, CorsPolicy $corsPolicy): never
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
    public function parseRequestBody(): void
    {
        $bodyDisabled = $this->getControllerAttribute(ControllerAttribute::disableRequestBody) ?: false;
        if ($bodyDisabled) {
            $bodyDisabled = !(($this->getControllerAttribute(ControllerAttribute::enableRequestBody) === true));
        }

        $allowFileUpload = $this->getControllerAttribute(ControllerAttribute::allowFileUpload) ?: false;
        $maxBodyBytes = $this->getConstraintOverride(RequestConstraint::maxBodyBytes);
        $maxParams = $this->getConstraintOverride(RequestConstraint::maxParams);
        $maxParamLength = $this->getConstraintOverride(RequestConstraint::maxParamLength);
        $maxDepth = $this->getConstraintOverride(RequestConstraint::dtoMaxDepth);

        try {
            $decoded = $this->middleware->requestBodyDecoderPipeline(
                $this->requestFacade,
                $bodyDisabled,
                $allowFileUpload,
                $maxBodyBytes,
                $maxParams,
                $maxParamLength,
                $maxDepth,
                $this->request->body ?? $this->request->bodyStreamPath
            );
        } catch (\Exception $e) {
            $errorCode = match (true) {
                $e instanceof \OutOfBoundsException => RequestError::ContentHandlingConflict,
                $e instanceof \OverflowException => RequestError::ContentOverflow,
                $e instanceof \UnderflowException => RequestError::MalformedBody,
                $e instanceof \LengthException => RequestError::BodyRequired,
                $e instanceof \DomainException => match ($e->getCode()) {
                    6 => RequestError::BodyDisabled,
                    5 => RequestError::UnsupportedTransferEncoding,
                    4 => RequestError::UnsupportedContentEncoding,
                    3 => RequestError::BadBodyCharset,
                    2 => RequestError::FileUploadDisabled,
                    default => RequestError::BadContentType,
                },
                default => RequestError::BodyDecodeError
            };

            throw new RequestGatewayException($errorCode, $e);
        }

        if ($decoded && $bodyDisabled) {
            throw new RequestGatewayException(RequestError::BodyDisabled,
                new \RuntimeException("Body disabled; Middleware returned " . get_debug_type($decoded)));
        }

        // Got Body?
        if ($decoded instanceof Buffer) {
            if (!$this->getControllerAttribute(ControllerAttribute::allowTextBody)) {
                throw new RequestGatewayException(RequestError::BadContentType,
                    new \RuntimeException("Text body disabled; Middleware returned " . get_debug_type($decoded)));
            }

            if ($decoded->length() > $maxBodyBytes) {
                throw new RequestGatewayException(RequestError::ContentOverflow, null);
            }
        }

        // Decoded Payload:
        if (is_array($decoded)) {
            if (count($decoded, COUNT_RECURSIVE) > $maxParams) {
                throw new RequestGatewayException(RequestError::ParamsOverflow,
                    new \RuntimeException("Maximum number of params: " . $maxParams));
            }

            if (ArrayHelper::checkDepth($decoded, $maxDepth + 1) > $maxDepth) {
                throw new RequestGatewayException(RequestError::ParamsOverflow,
                    new \RuntimeException("Maximum depth allowed: " . $maxDepth));
            }

            try {
                array_walk_recursive($decoded, function ($value, $key) use ($maxParamLength) {
                    if (is_string($value)) {
                        if (!is_string($key) || !AsciiHelper::isPrintableOnly($key) || preg_match("/\s/", $key)) {
                            throw new \InvalidArgumentException("Invalid param key received");
                        }

                        if (strlen($key) > 64) {
                            throw new \LengthException("Param key exceeds maximum length: 64 bytes");
                        }

                        if (strlen($value) > $maxParamLength) {
                            throw new RequestGatewayException(RequestError::ParamsOverflow,
                                new \RuntimeException("Maximum param length: " . $maxParamLength));
                        }
                    }
                });
            } catch (\Exception $e) {
                throw new RequestGatewayException(RequestError::ParamValidation, $e);
            }
        }

        // File Upload?
        if ($decoded instanceof FileUpload) {
            if (!$allowFileUpload || $decoded->size > $allowFileUpload["size"]) {
                throw new RequestGatewayException(RequestError::FileUploadDisabled,
                    new \RuntimeException("File upload disabled or exceeds maximum size"));
            }
        }

        // Initialize Facade Inputs
        try {
            $this->requestFacade->initializeBody($decoded);
        } catch (WrappedException $e) {
            throw new RequestGatewayException(RequestError::MalformedBody, $e->getPrevious());
        }
    }

    /**
     * @param ControllerAttribute $attr
     * @param bool $aggregated
     * @return mixed
     */
    public function getControllerAttribute(ControllerAttribute $attr, bool $aggregated = false): mixed
    {
        $entryPoint = $this->controllerEp;
        if ($attr === ControllerAttribute::constraints) {
            $entryPoint = null;
        }

        return $aggregated ? $this->routeController->controller->getAggregatedAttributeFor($attr, $entryPoint)
            : $this->routeController->controller->getAttributeFor($attr, $entryPoint);
    }

    /**
     * @param RequestConstraint $constraint
     * @return int|null
     */
    public function getConstraintOverride(RequestConstraint $constraint): ?int
    {
        return $this->getControllerAttribute(ControllerAttribute::constraints)[$constraint->name] ??
            $this->constraints->get($constraint) ?? null;
    }

    /**
     * @return SuccessResponseInterface
     * @throws RequestGatewayException
     */
    public function executeController(): SuccessResponseInterface
    {
        $gatewayFacade = $this->middleware->controllerGatewayFacadePipeline($this);
        $controllerContext = $this->routeController->controller;

        $gatewayFacade->enforceRequiredParams();
        $buffering = false;

        if (HttpServer::$enableOutputBuffering && HttpServer::$outputBufferToStdErr) {
            $buffering = ob_start();
        }

        try {
            try {
                // Construct Controller, dispatch "BeforeEntrypointCallback" hook
                $controller = new $controllerContext->classname($this);
                if ($controller instanceof BeforeEntrypointCallback) {
                    $controller->beforeEntrypointCallback($gatewayFacade);
                }

                // Dispatch Entrypoint
                if ($controller instanceof InvokableControllerInterface) {
                    $controller($gatewayFacade);
                } else {
                    call_user_func_array([$controller, $this->controllerEp], [$gatewayFacade]);
                }
            } catch (ResponseFinalizedInterrupt $e) {
                $this->setFinalizedResponse($e->getResponseObject());
            }

            // AfterEntrypointCallback is called even after response has already finalized via interrupt exception
            // However, once finalized response body cannot be altered
            try {
                assert(isset($controller));
                if ($controller instanceof AfterEntrypointCallback) {
                    $controller->afterEntrypointCallback($gatewayFacade);
                }
            } catch (ResponseFinalizedInterrupt $e) {
                $this->setFinalizedResponse($e->getResponseObject());
            }
        } catch (RequestGatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            if($e instanceof ValidationTranslatedException) {
                $e->setContextMessage($gatewayFacade);
            }

            if ($e instanceof ValidationExceptionInterface) {
                throw new RequestGatewayException(ControllerError::ValidationException, $e);
            }

            throw new RequestGatewayException(ControllerError::ExecutionFlow, $e);
        } finally {
            if ($buffering) {
                try {
                    HttpServer::flushOutputBuffer();
                } catch (\Throwable) {
                }
            }
        }

        if ($this->isResponseFinalized()) {
            return $this->finalizedResponse;
        }

        if ($this->response->count() === 0) {
            $this->setFinalizedResponse(new NoContentResponse($this->response->getStatusCode()));
            return $this->finalizedResponse;
        }

        $encodedBody = $this->encodeResponseBody();
        $this->setFinalizedResponse(new EncodedBufferResponse(
            $this->response->getStatusCode(),
            false,
            $encodedBody->buffer,
            $this->response->isCacheable(),
            $encodedBody->contentType,
            $encodedBody->charset
        ));

        return $this->finalizedResponse;
    }

    /**
     * @return EncodedResponseBody
     * @throws RequestGatewayException
     */
    private function encodeResponseBody(): EncodedResponseBody
    {
        try {
            return $this->middleware->responseBodyEncoderPipeline(
                $this->response->getContentType(),
                $this->response->charset,
                new PayloadImmutable($this->response)
            );
        } catch (\Exception $e) {
            throw new RequestGatewayException(RequestError::ResponseEncodeError, $e);
        }
    }

    /**
     * @param SuccessResponseInterface $response
     * @return void
     * @throws RequestGatewayException
     */
    private function setFinalizedResponse(SuccessResponseInterface $response): void
    {
        if (isset($this->finalizedResponse)) {
            throw new RequestGatewayException(ControllerError::RedundantResponseFinalized,
                new \RuntimeException("Response was already finalized; Redundant duplicate response"));
        }

        $this->finalizedResponse = $response;
    }

    /**
     * @return bool
     */
    public function isResponseFinalized(): bool
    {
        return isset($this->finalizedResponse);
    }
}