<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware;

use Charcoal\Http\Router\Contracts\Middleware\Group\GroupMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\KernelMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\Route\RouteMiddlewareInterface;
use Charcoal\Http\Router\Enums\Middleware\Scope;

/**
 * Defines a contract for implementing a trust policy for middleware handling.
 * Implementations can be as simple as a hard-coded allowlist of middleware FQCNs,
 * or consult config/env to allow more in dev and fewer in prod. Keep it fast and
 * deterministic (no I/O).
 */
interface MiddlewareTrustPolicyInterface
{
    /**
     * Checks if the provided middleware is accepted for the given scope.
     * @param class-string<MiddlewareInterface> $middleware
     * @param Scope $scope
     * @return bool
     * @api
     */
    public function isTrustedFqcn(string $middleware, Scope $scope): bool;

    /**
     * Determines whether the given middleware is trusted based on the provided scope.
     * @param KernelMiddlewareInterface|GroupMiddlewareInterface|RouteMiddlewareInterface $middleware
     * @param Scope $scope
     * @return bool
     * @api
     */
    public function isTrusted(
        KernelMiddlewareInterface|GroupMiddlewareInterface|RouteMiddlewareInterface $middleware,
        Scope                                                                       $scope
    ): bool;
}