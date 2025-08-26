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
use Charcoal\Http\Server\Config\ServerConfig;
use Charcoal\Http\Server\Enums\RequestError;
use Charcoal\Http\Server\Exceptions\HttpOptionsException;
use Charcoal\Http\Server\Exceptions\RequestContextException;
use Charcoal\Http\Server\Middleware\Registry\ResolverFacade;
use Charcoal\Http\Server\Request\Controller\ControllerApi;
use Charcoal\Http\Server\Routing\Snapshot\RouteControllerBinding;
use Charcoal\Http\Server\TrustProxy\TrustGateway;

/**
 * Represents the context of an HTTP request, encompassing details such as
 * request headers, payload, trust gateway information, and internal buffer states.
 * This class is designed to facilitate HTTP request handling, processing pipelines,
 * and error management during runtime.
 */
final readonly class RequestLifecycle
{
    use NoDumpTrait;
    use NotSerializableTrait;
    use NotCloneableTrait;

    /** @var string<non-empty-string> 16 bytes, Binary UUID */
    public string $requestId;
    public TrustGateway $gateway;
    public Headers $headers;
    public ?array $pathParams;
    public ?CorsPolicy $corsPolicy;
    public ?ContentType $contentType;
    public RouteControllerBinding $controllerMeta;
    public ControllerApi $controllerContext;
    public string $controllerEp;
    public UnsafePayload $input;
    public WritablePayload $response;
    public ?CacheControlDirectives $cacheControl;

    public function __construct(
        private ServerRequest  $request,
        private ResolverFacade $middleware,
    )
    {
        $this->headers = new Headers();
    }

    /**
     * @param ServerConfig $config
     * @return void
     * @throws RequestContextException
     */
    public function gatewayPipelines(ServerConfig $config): void
    {
        // 1. Resolve TrustGateway via trusted proxy CIDR
        $this->gateway = new TrustGateway($config, $this->request);

        // 2. Resolve a unique request ID
        try {
            $this->requestId = $this->middleware->kernel
                ->requestIdResolver()($this->request->headers)->raw();
        } catch (\Throwable $e) {
            throw new RequestContextException(RequestError::RequestIdError, $e);
        }

        // 3. URL Encoding Enforcer
        try {
            $redirect = $this->middleware->kernel->urlEncodingEnforcer()($this->request->url);
            if ($redirect) {
                throw RequestContextException::forRedirect(RequestError::UrlNormalizedRedirect, $redirect);
            }
        } catch (RequestContextException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RequestContextException(match ($e->getCode()) {
                414 => RequestError::BadUrlLength,
                default => RequestError::BadUrlEncoding
            }, $e);
        }
    }

    /**
     * @param array|null $pathParams
     * @return void
     * @throws HttpOptionsException
     * @throws RequestContextException
     */
    public function preFlightControl(?array $pathParams): void
    {
        $this->pathParams = $pathParams;

        // 4. Pre-Flight Control
        $origin = $this->request->headers->get("Origin");
        if ($origin) {
            try {
                $this->corsPolicy = $this->middleware->kernel->corsPolicyResolver()();
            } catch (\Throwable $e) {
                throw new RequestContextException(RequestError::CorsPolicyResolveError, $e);
            }
        }

        if (!isset($this->corsPolicy)) {
            $this->corsPolicy = null;
        }

        if ($this->request->method === HttpMethod::OPTIONS) {
            if ($this->corsPolicy) {
                if (!HttpHelper::isValidOrigin($origin)) {
                    throw new RequestContextException(RequestError::BadOriginHeader, null);
                }

                if ($this->corsPolicy->origins) {
                    if (!in_array(strtolower($origin), $this->corsPolicy->origins, true)) {
                        throw new RequestContextException(RequestError::CorsOriginNotAllowed, null);
                    }
                }

                throw new HttpOptionsException($origin, $this->corsPolicy);
            }

            throw new HttpOptionsException(null, $this->corsPolicy);
        }

        // Echo the received origin
        if ($this->corsPolicy) {
            $this->headers->set("Access-Control-Allow-Origin", $origin);
            $this->headers->set("Access-Control-Expose-Headers", $this->corsPolicy->expose);
            $this->headers->set("Vary", "Origin");
        }
    }

    public function routingResolved(RouteControllerBinding $controller, string $entryPoint): void
    {
        $this->controllerMeta = $controller;
        $this->controllerEp = $entryPoint;

        // 5. Create Controller Context
        try {
            $this->controllerContext = $this->middleware->kernel->controllerContextResolver()($this);
        } catch (\Throwable $e) {
            throw new RequestContextException(RequestError::ControllerContextResolveError, $e);
        }

        // 6. Require Request Body?
        $this->contentType = ContentType::find($this->headers->get("Content-Type") ?? "");
        if ($this->contentType) {
            $contentLength = (int)$this->headers->get("Content-Length");
            if ($contentLength <= 0) {
                $this->input = new UnsafePayload();
            }
        }

        if (!isset($this->input) && isset($contentLength) && $contentLength > 0) {
            try {
                $this->input = $this->middleware->kernel->requestBodyDecoder()($this->contentType, $contentLength);
            } catch (\Throwable $e) {
                throw new RequestContextException(RequestError::RequestBodyDecodeError, $e);
            }
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