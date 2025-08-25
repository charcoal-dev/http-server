<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Router\Config\Config;
use Charcoal\Http\Router\Enums\RequestError;
use Charcoal\Http\Router\Exceptions\RequestContextException;
use Charcoal\Http\Router\Middleware\Registry\ResolverFacade;

final class RequestContext
{
    /** @var string<non-empty-string> 16 bytes, Binary UUID */
    public readonly string $requestId;
    public readonly TrustGateway $gateway;
    public readonly Headers $headers;

    public function __construct(
        private readonly ServerRequest  $request,
        private readonly ResolverFacade $middleware,
    )
    {
        $this->headers = new Headers();

    }

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