<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware;

use Charcoal\Http\Router\Contracts\Middleware\Kernel\KernelMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\Group\GroupMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareTrustPolicyInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\RouteMiddlewareInterface;
use Charcoal\Http\Router\Enums\Middleware\Scope;
use Charcoal\Http\Router\Routing\Snapshot\AppRoutingSnapshot;

/**
 * Manages middleware registration and resolution within a specified scope.
 * Handles middleware validation, scope matching, and trusted policy checks.
 * Provides mechanisms to lock the registry to prevent further modifications.
 */
final class MiddlewareRegistry
{
    /** @var array<non-empty-string,MiddlewareInterface */
    private array $resolved = [
        Scope::Kernel->name => [],
        Scope::Group->name => [],
        Scope::Route->name => [],
    ];

    /**
     * Manages the registration and handling of middleware components.
     * Provides functionalities to resolve, allowlist, and enable middleware processing.
     */
    public function __construct(
        AppRoutingSnapshot                                 $routingSnapshot,
        protected readonly MiddlewareResolverInterface     $resolver,
        protected readonly ?MiddlewareTrustPolicyInterface $trustPolicy = null,
        protected bool                                     $isLocked = false,
    )
    {
        foreach ($routingSnapshot as $routeDto) {
            foreach ($routeDto->controllers as $controllerDto) {
                if (!$controllerDto->middleware) {
                    continue;
                }

                $getBag = $controllerDto->middleware;
            }
        }
    }

    /**
     * @return void
     * @internal
     */
    public function setLock(): void
    {
        $this->isLocked = true;
    }

    /**
     * @param Scope $scope
     * @param non-empty-string $contract
     * @param array $context
     * @return MiddlewareInterface
     * @internal
     */
    public function resolve(
        Scope  $scope,
        string $contract,
        array  $context = []
    ): MiddlewareInterface
    {
        $resolved = $this->resolved[$scope->name][$contract] ??
            $this->resolver->resolve($contract, $context);
        $this->resolved[$scope->name][$contract] = $resolved;
        return $resolved;
    }

    /**
     * Registers instanced middleware within the specified scope if it meets all validation requirements.
     * @param non-empty-string $contract
     * @api
     */
    public function registerInstanced(
        string              $contract,
        MiddlewareInterface $middleware,
        Scope               $scope
    ): void
    {
        if ($this->isLocked) {
            throw new \RuntimeException("Middleware registry is locked");
        }

        $contractScope = match (true) {
            $middleware instanceof KernelMiddlewareInterface => Scope::Kernel,
            $middleware instanceof GroupMiddlewareInterface => Scope::Group,
            $middleware instanceof RouteMiddlewareInterface => Scope::Route,
            default => throw new \InvalidArgumentException("Middleware must implement one of: " .
                "KernelMiddlewareInterface, GroupMiddlewareInterface, RouteMiddlewareInterface"),
        };

        if ($contractScope !== $scope) {
            throw new \OutOfBoundsException("Middleware does not meet scope requirement: " . $contractScope->name);
        }

        if (!interface_exists($contract) || !$middleware instanceof $contract) {
            throw new \BadMethodCallException("Middleware does not implement the contract: " . $contract);
        }

        if (!in_array($contract, $scope->getRegisteredPipelines())) {
            if ($this->trustPolicy?->isTrusted($middleware, $scope) !== true) {
                throw new \DomainException("Middleware is not registered nor whitelisted: " . $middleware::class);
            }
        }

        if (isset($this->resolved[$scope->name][$contract]) ||
            isset($this->factories[$scope->name][$contract])) {
            throw new \DomainException("Middleware for the contract is already registered: " . $contract);
        }

        $this->resolved[$scope->name][$contract] = $middleware;
    }
}