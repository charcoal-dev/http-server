<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Group;

use Charcoal\Http\Router\Enums\Middleware\Scope;
use Charcoal\Http\Router\Exceptions\RoutingBuilderException;
use Charcoal\Http\Router\Middleware\Bag\Bag;
use Charcoal\Http\Router\Middleware\Bag\SealedBag;
use Charcoal\Http\Router\Routing\Route;
use Charcoal\Http\Router\Routing\RouteBuilder;

/**
 * Abstract class that represents a group of routes.
 * Provides functionality to define namespaces, paths, and manage child routes
 * or route groups within a larger routing structure.
 */
abstract readonly class AbstractRouteGroup
{
    public string $path;
    public array $children;
    protected Bag $middlewareOwn;
    protected SealedBag $middleware;

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

        try {
            $this->middlewareOwn = Bag::create(Scope::Group);
            $groupPolicy = new RouteGroupBuilder($this);
            $declaration($groupPolicy);
            $this->middlewareOwn->lock();
            $this->build($groupPolicy);
        } catch (RoutingBuilderException $e) {
            throw $e;
        } catch (\Throwable $t) {
            throw new RoutingBuilderException("Group [" . $this->path . "]: " . $t->getMessage(), previous: $t);
        }
    }

    /**
     * @throws RoutingBuilderException
     */
    protected function build(RouteGroupBuilder $group): void
    {
        $children = [];
        $groupPolicies = $group->attributes();
        $chain = $this->getAggregatedPath();
        array_pop($chain);
        $middleware = $this->getAggregatedMiddleware();
        array_pop($middleware);
        $middlewareAgr = Bag::merge(Scope::Group, ...$middleware)->lock();
        $this->middleware = new SealedBag($this->middlewareOwn, $middlewareAgr);

        // Children
        $num = 0;
        foreach ($groupPolicies[0] as $child) {
            $num++;
            try {
                if (!$child instanceof RouteBuilder && !$child instanceof RouteGroup) {
                    throw new \UnexpectedValueException("Unsupported child element: " . get_debug_type($child));
                }

                if ($child instanceof RouteBuilder) {
                    $routePolicies = $child->attributes();
                    $routePipelines = $routePolicies[1];
                    $routePipelines->lock();
                    $route = new Route($child->path, $child->classname, $routePolicies[0]);
                    $route->finalize(new SealedBag($routePipelines, $middlewareAgr));
                    $this->appendChild($children, $route);
                }

                if ($child instanceof RouteGroup) {
                    $this->appendChild($children, $child);
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
        $this->middlewareOwn->set(...$pipelines);
        return $this;
    }

    /**
     * @return array
     */
    protected function getAggregatedPath(): array
    {
        if (!$this->parent) {
            return [""];
        }

        return [...$this->parent->getAggregatedPath(), $this->path];
    }

    /**
     * @return array
     */
    protected function getAggregatedMiddleware(): array
    {
        if (!$this->parent) {
            return [Bag::create(Scope::Group)->lock()];
        }

        return [...$this->parent->getAggregatedMiddleware(), $this->middlewareOwn->lock()];
    }

    /**
     * @param array $children
     * @param Route|RouteGroup $child
     * @return void
     */
    private function appendChild(array &$children, Route|RouteGroup $child): void
    {
        $children[] = $child;
    }
}