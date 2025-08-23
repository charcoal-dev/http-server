<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware;

use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareConstructableInterface;
use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareFactoryInterface;
use Charcoal\Http\Router\Contracts\Middleware\Global\GlobalMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\Group\GroupMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\RouteMiddlewareInterface;
use Charcoal\Http\Router\Enums\Middleware\Scope;

/**
 * A storage bag for managing middleware components.
 * This class provides a structure to hold and manage an array of middleware
 * used in an application. Middleware usually refers to the components that process
 */
final class MiddlewareBag
{
    /** @var string */
    private readonly string $baseContract;
    /** @var array<class-string<MiddlewareInterface>> */
    private array $middleware = [];
    private bool $locked = false;

    /**
     * @param Scope $scope
     */
    public function __construct(public readonly Scope $scope)
    {
        $this->baseContract = match ($scope) {
            Scope::Global => GlobalMiddlewareInterface::class,
            Scope::Group => GroupMiddlewareInterface::class,
            Scope::Route => RouteMiddlewareInterface::class,
        };
    }

    /**
     * @return $this
     */
    public function lock(): self
    {
        $this->locked = true;
        return $this;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->middleware;
    }

    /**
     * @param string ...$middleware
     * @return void
     */
    public function set(string ...$middleware): void
    {
        foreach ($middleware as $m) {
            $this->store($m);
        }
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Stores a middleware class after validating its existence, instability,
     * contract implementation, and proper interface implementation.
     */
    protected function store(string $middleware): void
    {
        if ($this->locked) {
            throw new \BadMethodCallException("Middleware bag is locked and cannot be modified");
        }

        if (!class_exists($middleware)) {
            throw new \InvalidArgumentException("Middleware class does not exist");
        }

        $reflect = new \ReflectionClass($middleware);
        if (!$reflect->isInstantiable() && !$reflect->implementsInterface(MiddlewareFactoryInterface::class)) {
            throw new \InvalidArgumentException("Middleware class must be instantiable");
        }

        if (!$reflect->implementsInterface($this->baseContract)) {
            throw new \DomainException("Middleware class must implement " . $this->baseContract);
        }

        if (!$reflect->implementsInterface(MiddlewareConstructableInterface::class) &&
            !$reflect->implementsInterface(MiddlewareFactoryInterface::class)) {
            throw new \OutOfBoundsException("Middleware class must implement one of the following interfaces: " .
                MiddlewareConstructableInterface::class . " or " . MiddlewareFactoryInterface::class);
        }

        if (!$reflect->isFinal()) {
            throw new \InvalidArgumentException("Middleware class must be declared final");
        }

        $this->middleware[] = $middleware;
    }
}