<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Registry;

use Charcoal\Http\Router\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Router\Contracts\Middleware\Group\GroupMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\KernelMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareTrustPolicyInterface;
use Charcoal\Http\Router\Contracts\Middleware\Route\RouteMiddlewareInterface;
use Charcoal\Http\Router\Enums\Middleware\Scope;
use Charcoal\Http\Router\Middleware\MiddlewareConstructor;
use Charcoal\Http\Router\Routing\Snapshot\AppRoutingSnapshot;

/**
 * Manages middleware registration and resolution within a specified scope.
 * Handles middleware validation, scope matching, and trusted policy checks.
 * Provides mechanisms to lock the registry to prevent further modifications.
 * @internal
 */
final class RouterMiddleware
{
    /** @var array<non-empty-string,MiddlewareInterface> */
    private array $kernelResolved = [];
    /** @var array<non-empty-string,MiddlewareConstructor> */
    private array $kernelFactories = [];

    /** @var array<non-empty-string,array<class-string<ControllerInterface>,MiddlewareInterface> */
    private array $resolved = [
        Scope::Group->name => [],
        Scope::Route->name => [],
    ];

    /** @var array<non-empty-string,array<class-string<ControllerInterface>,MiddlewareConstructor> */
    private array $factories = [
        Scope::Group->name => [],
        Scope::Route->name => [],
    ];

    private ResolverFacade $resolverIndex;

    /**
     * Manages the registration and handling of middleware components.
     * Provides functionalities to resolve, allowlist, and enable middleware processing.
     */
    public function __construct(
        AppRoutingSnapshot                               $routingSnapshot,
        private readonly MiddlewareResolverInterface     $resolver,
        private readonly ?MiddlewareTrustPolicyInterface $trustPolicy = null,
        private bool                                     $isLocked = false,
    )
    {
        $this->resolverIndex = new ResolverFacade($this);
        foreach ($routingSnapshot as $routeDto) {
            foreach ($routeDto->controllers as $controllerDto) {
                if (!$controllerDto->middleware) {
                    continue;
                }

                foreach ($controllerDto->middleware as $cnt) {
                    if (!$cnt->validated) {
                        throw new \DomainException(
                            "Middleware constructor was not validated: " .
                            $cnt->classname
                        );
                    }

                    foreach ($cnt->binds as $interface) {
                        $this->factories[$cnt->scope->name][$interface] = $cnt;
                    }
                }
            }
        }
    }

    /**
     * @return ResolverFacade
     */
    public function facade(): ResolverFacade
    {
        return $this->resolverIndex;
    }

    /**
     * @return MiddlewareResolverInterface
     * @api
     */
    public function factory(): MiddlewareResolverInterface
    {
        return $this->resolver;
    }

    /**
     * @return MiddlewareTrustPolicyInterface|null
     * @api
     */
    public function trustPolicy(): ?MiddlewareTrustPolicyInterface
    {
        return $this->trustPolicy;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [
            "resolved" => null,
            "kernelResolved" => null,
            "resolver" => $this->resolver,
            "factories" => $this->factories,
            "kernelFactories" => $this->kernelFactories,
            "trustPolicy" => $this->trustPolicy,
            "isLocked" => $this->isLocked,
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->resolver = $data["resolver"];
        $this->factories = $data["factories"];
        $this->kernelFactories = $data["kernelFactories"];
        $this->trustPolicy = $data["trustPolicy"];
        $this->isLocked = $data["isLocked"];
        $this->kernelResolved = [];
        $this->resolved = [
            Scope::Group->name => [],
            Scope::Route->name => [],
        ];
    }

    /**
     * Can be triggered from Router constructor callback
     * @return void
     * @api
     */
    public function setLock(): void
    {
        $this->isLocked = true;
    }

    /**
     * @param string $contract
     * @return KernelMiddlewareInterface|callable
     */
    public function resolveGlobal(string $contract): KernelMiddlewareInterface|callable
    {
        return $this->resolveInternal(Scope::Kernel, $contract);
    }

    /**
     * @param string $contract
     * @param ControllerInterface $controller
     * @param Scope $scope
     * @param array|null $context
     * @return GroupMiddlewareInterface|RouteMiddlewareInterface|callable
     * @internal
     */
    public function resolveFor(
        string              $contract,
        ControllerInterface $controller,
        Scope               $scope = Scope::Group,
        ?array              $context = null
    ): GroupMiddlewareInterface|RouteMiddlewareInterface|callable
    {
        if ($scope === Scope::Kernel) {
            throw new \BadMethodCallException("Cannot resolve middleware for kernel scope: " . $scope->name);
        }

        return $this->resolveInternal($scope, $contract, $controller, $context);
    }

    /**
     * @param Scope $scope
     * @param string $contract
     * @param ControllerInterface|null $controller
     * @param array|null $context
     * @return MiddlewareInterface|callable
     * @noinspection PhpUnusedParameterInspection
     */
    private function resolveInternal(
        Scope                $scope,
        string               $contract,
        ?ControllerInterface $controller = null,
        ?array               $context = null
    ): MiddlewareInterface|callable
    {
        if ($scope === Scope::Kernel) {
            $resolved = $this->kernelResolver[$scope->name][$contract] ??
                $this->kernelFactories[$scope->name][$contract] ?? null;
        } else {
            if (!$controller) {
                throw new \BadMethodCallException("Controller is required for non-kernel scope: " . $scope->name);
            }

            $resolved = $this->resolved[$scope->name][$controller::class][$contract] ??
                $this->factories[$scope->name][$controller::class][$contract] ?? null;
        }

        if ($resolved instanceof MiddlewareInterface) {
            return $resolved;
        }

        if ($resolved instanceof MiddlewareConstructor) {
            if (!$resolved->validated) {
                throw new \RuntimeException("Unvalidated middleware constructor: " .
                    $resolved->classname);
            }

            try {
                $middleware = match (true) {
                    $resolved->isFactory => call_user_func_array([$resolved->classname, "create"],
                        $resolved->arguments ?: []),
                    default => new ($resolved->classname)(...$resolved->arguments ?: []),
                };
            } catch (\Throwable $t) {
                throw new \RuntimeException("Failed to instantiate middleware: " .
                    $resolved->classname, previous: $t);
            }

            foreach ($resolved->binds as $bind) {
                if ($scope === Scope::Kernel) {
                    $this->kernelResolved[$scope->name][$bind] = $middleware;
                } else {
                    $this->resolved[$scope->name][$controller::class][$bind] = $middleware;
                }
            }

            return $middleware;
        }

        if ($scope === Scope::Kernel) {
            $resolved = $this->resolver->resolveForKernel($contract);
            $this->kernelResolved[$scope->name][$contract] = $resolved;
        } else {
            $resolved = $this->resolver->resolveFor($contract, $controller, $scope);
            $this->resolved[$scope->name][$controller::class][$contract] = $resolved;
        }

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

        if (!in_array($contract, $scope->getRegisteredPipelines(), true)) {
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