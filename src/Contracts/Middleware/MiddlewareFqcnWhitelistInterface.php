<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware;

use Charcoal\Http\Router\Enums\Middleware\Scope;

/**
 * Defines an interface for validating if middleware is trusted
 * based on its fully qualified name (FQN) and the provided scope.
 */
interface MiddlewareFqcnWhitelistInterface
{
    /**
     * Checks if the provided middleware is accepted for the given scope.
     * @param class-string<MiddlewareInterface> $middleware
     * @param Scope $scope
     * @return bool
     * @api
     */
    public function isAccepted(string $middleware, Scope $scope): bool;
}