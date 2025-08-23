<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware;

use Charcoal\Http\Router\Contracts\Middleware\Global\GlobalMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\GlobalMiddlewareResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\Group\GroupMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareTrustPolicyInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\RouteMiddlewareInterface;
use Charcoal\Http\Router\Enums\Middleware\Scope;
use Charcoal\Http\Router\Routing\Snapshot\AppRoutingSnapshot;

/**
 * Manages the registration and handling of middleware components.
 * Provides functionalities to resolve, allowlist, and enable middleware processing.
 */
final class MiddlewareRegistry
{
    private array $resolved = [
        Scope::Global->name => [],
        Scope::Group->name => [],
        Scope::Route->name => [],
    ];

    private array $factories = [
        Scope::Global->name => [],
        Scope::Group->name => [],
        Scope::Route->name => [],
    ];

    public function __construct(
        AppRoutingSnapshot                                   $routingSnapshot,
        protected readonly GlobalMiddlewareResolverInterface $resolver,
        protected readonly ?MiddlewareTrustPolicyInterface   $trustPolicy = null,
        protected bool                                       $isLocked = false,
    )
    {
    }

    public function lock(): void
    {
        $this->isLocked = true;
    }

    /**
     * There will not be persist.
     */
    public function registerInstanced(
        string              $binds,
        MiddlewareInterface $middleware,
        Scope               $scope
    ): void
    {
        if ($this->isLocked) {
            throw new \RuntimeException("Middleware registry is locked");
        }

        $contractScope = match (true) {
            $middleware instanceof GlobalMiddlewareInterface => Scope::Global,
            $middleware instanceof GroupMiddlewareInterface => Scope::Group,
            $middleware instanceof RouteMiddlewareInterface => Scope::Route,
            default => throw new \InvalidArgumentException("Middleware must implement one of: " .
                "GlobalMiddlewareInterface, GroupMiddlewareInterface, RouteMiddlewareInterface"),
        };

        if ($contractScope !== $scope) {
            throw new \OutOfBoundsException("Middleware does not meet scope requirement: " . $contractScope->name);
        }

        if (!$middleware instanceof $binds) {
            throw new \BadMethodCallException("Middleware does not implement the contract: " . $binds);
        }

        if (!in_array($binds, $scope->getRegisteredPipelines())) {
            if ($this->trustPolicy?->isTrusted($middleware, $scope) !== true) {
                throw new \DomainException("Middleware is not registered nor whitelisted: " . $middleware::class);
            }
        }

        if (isset($this->resolved[$scope->name][$binds]) ||
            isset($this->factories[$scope->name][$binds])) {
            throw new \DomainException("Middleware for the contract is already registered: " . $binds);
        }

        $this->resolved[$scope->name][$binds] = $middleware;
    }


    public function resolve(string $binds, Scope $scope): void
    {

    }
}