<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware;

use Charcoal\Base\Support\Helpers\DtoHelper;
use Charcoal\Http\Router\Attributes\BindsTo;
use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareConstructableInterface;
use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareFactoryInterface;
use Charcoal\Http\Router\Contracts\Middleware\Global\GlobalMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\Group\GroupMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\RouteMiddlewareInterface;
use Charcoal\Http\Router\Enums\Middleware\Scope;

/**
 * Represents a constructor for middleware, validating and ensuring its adherence
 * to specific scopes and interface requirements during instantiation.
 * @property string<class-string<MiddlewareInterface>> $classname
 */
final readonly class MiddlewareConstructor
{
    /** @var array<class-string<MiddlewareInterface>> */
    public array $binds;
    public array $arguments;
    public bool $validated;

    /**
     * @param Scope $scope
     * @param class-string<MiddlewareInterface> $classname
     * @param array|null $arguments scalar values only
     * @param bool $isTesting
     */
    public function __construct(
        Scope         $scope,
        public string $classname,
        ?array        $arguments = null,
        bool          $isTesting = false,
    )
    {
        $this->arguments = DtoHelper::createFrom($arguments, maxDepth: 2);

        if ($isTesting) {
            $this->validated = false;
            $this->binds = $this->tryCheckBindings($classname);
            return;
        }

        if (!class_exists($classname)) {
            throw new \InvalidArgumentException("Middleware class does not exist");
        }

        $reflect = new \ReflectionClass($classname);
        if (!$reflect->isInstantiable() && !$reflect->implementsInterface(MiddlewareFactoryInterface::class)) {
            throw new \InvalidArgumentException("Middleware class must be instantiable: " . $classname);
        }

        if (!$reflect->isFinal()) {
            throw new \InvalidArgumentException("Middleware class must be declared final: " . $classname);
        }

        $baseContract = match ($scope) {
            Scope::Global => GlobalMiddlewareInterface::class,
            Scope::Group => GroupMiddlewareInterface::class,
            Scope::Route => RouteMiddlewareInterface::class,
        };

        if (!$reflect->implementsInterface($baseContract)) {
            throw new \DomainException("Middleware class must implement :" . $baseContract);
        }

        // Implements one of our known constructor interfaces
        if (!$reflect->implementsInterface(MiddlewareConstructableInterface::class) &&
            !$reflect->implementsInterface(MiddlewareFactoryInterface::class)) {
            throw new \InvalidArgumentException("Middleware class must implement one of the following interfaces: " .
                MiddlewareConstructableInterface::class . " or " . MiddlewareFactoryInterface::class);
        }

        // Get bindings
        $binds = array_map(fn($a) => $a->newInstance()->contract, $reflect->getAttributes(BindsTo::class));
        foreach ($binds as $bind) {
            if (!class_exists($bind)) {
                throw new \InvalidArgumentException("Binding contract does not exist: " . $bind);
            }

            if (!$reflect->implementsInterface($bind)) {
                throw new \DomainException("Binding contract must implement: " . $bind);
            }

            // Cross-check referenced interface
            $ifReflect = new \ReflectionClass($bind);
            if (!$ifReflect->implementsInterface($baseContract)) {
                throw new \DomainException("Binding contract must implement: " . $baseContract);
            }
        }

        $this->binds = $binds;
        $this->validated = true;
    }

    private function tryCheckBindings(string $classname): array
    {
        try {
            if (class_exists($classname)) {
                $reflect = new \ReflectionClass($classname);
                return array_map(fn($a) => $a->newInstance()->contract, $reflect->getAttributes(BindsTo::class));
            }
        } catch (\Exception) {
        }

        return [];
    }
}