<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Body\Payload;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Router\Config\Config;
use Charcoal\Http\Router\Enums\RequestError;
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
    public Payload $payload;
    public Buffer $buffer;

    public function __construct(
        private ServerRequest  $request,
        private ResolverFacade $middleware,
    )
    {
        $this->headers = new Headers();

    }

    /**
     * @param Config $config
     * @return void
     * @throws RequestContextException
     */
    public function gatewayPipelines(Config $config): void
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
}