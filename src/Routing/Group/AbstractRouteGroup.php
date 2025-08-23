<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Group;

use Charcoal\Http\Router\Contracts\PathHolderInterface;
use Charcoal\Http\Router\Enums\Middleware\Scope;
use Charcoal\Http\Router\Exceptions\RoutingBuilderException;
use Charcoal\Http\Router\Middleware\MiddlewareBag;
use Charcoal\Http\Router\Routing\AppRoutes;
use Charcoal\Http\Router\Routing\Route;
use Charcoal\Http\Router\Routing\RouteBuilder;

/**
 * Abstract class that represents a group of routes.
 * Provides functionality to define namespaces, paths, and manage child routes
 * or route groups within a larger routing structure.
 */
abstract readonly class AbstractRouteGroup implements PathHolderInterface
{
    public string $path;
    public array $children;
    protected MiddlewareBag $middleware;
    public string $uniqueId;

    /**
     * @param RouteGroup|null $parent
     * @param string $path
     * @param \Closure(RouteGroupBuilder $group): void $declaration
     * @throws RoutingBuilderException
     */
    public function __construct(
        protected ?AbstractRouteGroup $parent,
        string                        $path,
        \Closure                      $declaration,
    )
    {
        $path = trim($path, "/");
        $this->path = match (true) {
            !$parent && $path === "" => "/",
            !$parent && $path => throw new \InvalidArgumentException("Root route must be '/' precise"),
            $path && preg_match('/^(?:[A-Za-z0-9_.-]*[A-Za-z0-9]|:[A-Za-z0-9]+)(?:\/(?:[A-Za-z0-9_.-]*[A-Za-z0-9]|:[A-Za-z0-9]+))*$/', $path) => $path,
            default => throw new \InvalidArgumentException("Route prefix is invalid " . $path),
        };

        $this->middleware = new MiddlewareBag(Scope::Group);
        $groupPolicy = new RouteGroupBuilder($this);
        $declaration($groupPolicy);
        $this->build($groupPolicy);
    }

    /**
     * @throws RoutingBuilderException
     */
    protected function build(RouteGroupBuilder $group): void
    {
        $children = [];
        $groupPolicies = $group->attributes();
        $root = $this->getRootNode();
        $chain = $this->getAggregatedPath();
        $this->uniqueId = $root->generateUniqueId($this, $chain);
        $chain[] = $this->path;
        $middleware = $this->getAggregatedMiddleware();

        // Children
        $tracker = [];
        $num = 0;
        foreach ($groupPolicies[0] as $child) {
            $num++;
            try {
                if (!$child instanceof RouteBuilder && !$child instanceof RouteGroup) {
                    throw new \UnexpectedValueException("Unsupported child element: " . get_debug_type($child));
                }

                if ($child instanceof RouteBuilder) {
                    $routePolicies = $child->attributes();
                    $route = new Route($child->path, $child->classname, $routePolicies[0], $routePolicies[1]);
                    $uniqueId = $root->generateUniqueId($route, $chain);
                    $route->setUniqueId($uniqueId);
                    $this->appendChild($children, $tracker, $route, $uniqueId);
                }

                if ($child instanceof RouteGroup) {
                    $uniqueId = $child->getUniqueId();
                    $this->appendChild($children, $tracker, $child, $uniqueId);
                }
            } catch (\Throwable $t) {
                throw new RoutingBuilderException("Group [" . $this->path . "][#" . $num . "]: " .
                    $t->getMessage(), previous: $t);
            }
        }

        $this->children = $children;
    }

    /**
     * @param string ...$pipelines
     * @return $this
     */
    public function pipelines(string ...$pipelines): self
    {
        $this->middleware->set(...$pipelines);
        return $this;
    }

    /**
     * @return AppRoutes
     */
    protected function getRootNode(): AppRoutes
    {
        if ($this instanceof AppRoutes) {
            return $this;
        }

        return $this->parent->getRootNode();
    }

    /**
     * @return array
     */
    protected function getAggregatedPath(): array
    {
        if ($this->path === "/") {
            return [""];
        }

        return [...$this->parent?->getAggregatedPath() ?? []];
    }

    /**
     * @return array
     */
    protected function getAggregatedMiddleware(): array
    {
        $total = $this->middleware->lock()->all();
        if ($this->parent) {
            $total = [...$total, ...$this->parent->getAggregatedMiddleware()];
        }

        return $total;
    }

    /**
     * @param array $children
     * @param array $tracker
     * @param Route|RouteGroup $child
     * @param string $uniqueId
     * @return void
     */
    private function appendChild(array &$children, array &$tracker, Route|RouteGroup $child, string $uniqueId): void
    {
        if (isset($tracker[$uniqueId])) {
            throw new \OutOfBoundsException("Duplicate route path: " . $child->path);
        }

        $tracker[$uniqueId] = true;
        $children[] = $child;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }
}