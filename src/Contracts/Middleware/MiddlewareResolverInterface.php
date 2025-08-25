<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware;

use Charcoal\Http\Router\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Router\Enums\Middleware\Scope;

/**
 * Defines a contract for creating middleware components.
 * Extends the MiddlewareFactoryInterface to include specific resolver methods.
 */
interface MiddlewareResolverInterface
{
    /**
     * Resolves a middleware instance based on the provided contract and context.
     */
    public function resolveFor(
        string              $contract,
        ControllerInterface $controller,
        Scope               $scope = Scope::Group,
        ?array              $context = null
    ): MiddlewareInterface|callable;

    /**
     * Resolves a middleware instance or a callable for the given kernel pipeline.
     */
    public function resolveForKernel(string $contract): MiddlewareInterface|callable;
}