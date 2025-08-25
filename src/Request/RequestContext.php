<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

use Charcoal\Http\Commons\Body\Payload;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Support\HttpHelper;
use Charcoal\Http\Router\Config\RouterConfig;
use Charcoal\Http\Router\Enums\RequestError;
use Charcoal\Http\Router\Exceptions\HttpOptionsException;
use Charcoal\Http\Router\Exceptions\RequestContextException;
use Charcoal\Http\Router\Middleware\Registry\ResolverFacade;

/**
 * Represents the context of an HTTP request, encompassing details such as
 * request headers, payload, trust gateway information, and internal buffer states.
 * This class is designed to facilitate HTTP request handling, processing pipelines,
 * and error management during runtime.
 */
final readonly class RequestContext
{
    /** @var string<non-empty-string> 16 bytes, Binary UUID */
    public string $requestId;
    public TrustGateway $gateway;
    public Headers $headers;
    public ?CorsPolicy $corsPolicy;
    public Payload $payload;

    public function __construct(
        private ServerRequest  $request,
        private ResolverFacade $middleware,
    )
    {
        $this->headers = new Headers();
    }

    /**
     * @param RouterConfig $config
     * @return void
     * @throws RequestContextException
     */
    public function gatewayPipelines(RouterConfig $config): void
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

    public function preFlightControl(): void
    {
        // 4. Pre-Flight Control
        $origin = $this->request->headers->get("Origin");
        if ($origin) {
            try {
                $this->corsPolicy = $this->middleware->kernel->corsPolicyResolver()();
            } catch (\Throwable $e) {
                throw new RequestContextException(RequestError::KernelError, $e);
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

        $this->payload = new WritablePayload();
    }
}