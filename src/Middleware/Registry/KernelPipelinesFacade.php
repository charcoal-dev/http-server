<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Registry;

use Charcoal\Http\Commons\Body\PayloadImmutable;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\KernelMiddlewareInterface;
use Charcoal\Http\Router\Controllers\ControllerContext;
use Charcoal\Http\Router\Enums\Middleware\KernelPipelines;
use Charcoal\Http\Router\Request\CorsPolicy;
use Charcoal\Http\Router\Request\RequestContext;

/**
 * Provides a facade for kernel-level pipelines, enabling the resolution and access to key parts.
 */
final readonly class KernelPipelinesFacade
{
    public function __construct(private RouterMiddleware $registry)
    {
    }

    /**
     * Resolves and returns the KernelMiddlewareInterface instance from the registry
     * based on the provided pipeline and optional context.
     */
    public function resolve(KernelPipelines $pipeline): KernelMiddlewareInterface|callable
    {
        return $this->registry->resolveGlobal($pipeline->value);
    }

    /**
     * Resolves and returns the RequestIdResolverInterface instance from the registry
     * using the specified scope and interface class.
     * @return callable(HeadersImmutable $headers): string
     */
    public function requestIdResolver(): callable
    {
        return $this->resolve(KernelPipelines::RequestID_Resolver);
    }

    /**
     * Resolves and returns the UrlEncodingEnforcer instance from the registry
     * using the specified scope and interface class.
     * @return callable(UrlInfo $url): ?string
     */
    public function urlEncodingEnforcer(): callable
    {
        return $this->resolve(KernelPipelines::URL_EncodingEnforcer);
    }

    /**
     * Resolves and returns the CorsPolicyResolverInterface instance from the registry
     * using the specified scope and interface class.
     * @return callable(string $origin): CorsPolicy
     */
    public function corsPolicyResolver(): callable
    {
        return $this->resolve(KernelPipelines::CORS_PolicyResolver);
    }

    /**
     * Resolves and returns the RequestBodyDecoder callable instance from the registry
     * using the specified scope and interface class.
     * @return callable(ContentType $contentType, int $length): ?UnsafePayload
     */
    public function requestBodyDecoder(): callable
    {
        return $this->resolve(KernelPipelines::RequestBodyDecoder);
    }

    /**
     * Resolves and returns the ControllerContextResolverInterface instance from the registry.
     * @return callable(RequestContext $request): ?ControllerContext
     */
    public function controllerContextResolver(): callable
    {
        return $this->resolve(KernelPipelines::ControllerContextResolver);
    }

    /**
     * Resolves and returns the RequestBodyDecoder callable instance from the registry
     * using the specified scope and interface class.
     * @return callable(ContentType $contentType, PayloadImmutable $payload): mixed
     */
    public function responseBodyEncoder(): callable
    {
        return $this->resolve(KernelPipelines::RequestBodyDecoder);
    }
}