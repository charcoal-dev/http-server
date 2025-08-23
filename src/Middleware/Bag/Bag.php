<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Bag;

use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Vectors\AbstractVector;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;
use Charcoal\Http\Router\Enums\Middleware\Scope;
use Charcoal\Http\Router\Middleware\MiddlewareConstructor;
use Charcoal\Http\Router\Router;

/**
 * A storage bag for managing middleware components.
 * This class provides a structure to hold and manage an array of middleware
 * used in an application. Middleware usually refers to the components that process
 * @template T of MiddlewareConstructor
 */
final class Bag extends AbstractVector
{
    use NoDumpTrait;

    private bool $locked = false;

    /**
     * @param Scope $scope
     * @return self
     */
    public static function create(Scope $scope): self
    {
        return new self($scope, [], Router::$validateMiddlewareClasses);
    }

    /**
     * @param Scope $scope
     * @param Bag ...$bags
     * @return self
     */
    public static function merge(Scope $scope, Bag ...$bags): self
    {
        $collection = [];
        foreach ($bags as $bag) {
            $collection = [...$collection, ...$bag->getArray()];
        }

        return new self($scope, $collection, Router::$validateMiddlewareClasses);
    }

    /**
     * @param Scope $scope
     * @param array $previous
     * @param bool $testing
     */
    private function __construct(
        public readonly Scope $scope,
        array                 $previous,
        public readonly bool  $testing = false
    )
    {
        parent::__construct($previous);
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
     * @param class-string<MiddlewareInterface> ...$middleware
     * @return $this
     */
    public function set(string ...$middleware): self
    {
        if ($this->locked) {
            throw new \BadMethodCallException("Middleware bag is locked and cannot be modified");
        }

        foreach ($middleware as $m) {
            $this->values[] = new MiddlewareConstructor($this->scope, $m, isTesting: Router::$validateMiddlewareClasses);
        }

        return $this;
    }

    /**
     * @param class-string<MiddlewareInterface> $classname
     * @param array|null $arguments
     * @return $this
     * @api
     */
    public function setCustom(string $classname, ?array $arguments = null): self
    {
        if ($this->locked) {
            throw new \BadMethodCallException("Middleware bag is locked and cannot be modified");
        }

        $this->values[] = new MiddlewareConstructor(
            $this->scope,
            $classname,
            $arguments,
            isTesting: Router::$validateMiddlewareClasses
        );

        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }
}