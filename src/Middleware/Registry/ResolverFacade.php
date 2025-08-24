<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Registry;

use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;
use Charcoal\Http\Router\Enums\Middleware\Scope;

/**
 * A facade that interacts with the RouterMiddleware for resolving middleware interfaces.
 */
final readonly class ResolverFacade
{
    public KernelPipelinesFacade $kernel;

    public function __construct(private RouterMiddleware $registry)
    {
        $this->kernel = new KernelPipelinesFacade($this->registry);
    }

    /**
     * Resolves a middleware interface based on the given scope, interface, and context.
     */
    public function resolve(Scope $scope, string $interface, array $context = []): MiddlewareInterface
    {
        return $this->registry->resolve($scope, $interface, $context);
    }
}