<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Registry;

use Charcoal\Http\Router\Contracts\Middleware\Kernel\KernelMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\RequestIdResolverInterface;
use Charcoal\Http\Router\Enums\Middleware\KernelPipelines;
use Charcoal\Http\Router\Enums\Middleware\Scope;

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
    public function resolve(KernelPipelines $pipeline, array $context = []): KernelMiddlewareInterface
    {
        /** @var KernelMiddlewareInterface */
        return $this->registry->resolve(Scope::Kernel, $pipeline->value, $context);
    }

    /**
     * Resolves and returns the RequestIdResolverInterface instance from the registry
     * using the specified scope and interface class.
     */
    public function requestIdResolver(): RequestIdResolverInterface
    {
        /** @var RequestIdResolverInterface */
        return $this->resolve(KernelPipelines::RequestID_Resolver);
    }
}