<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

use Charcoal\Http\Router\Exceptions\RoutingBuilderException;
use Charcoal\Http\Router\Routing\Group\AbstractRouteGroup;
use Charcoal\Http\Router\Routing\Group\RouteGroup;
use Charcoal\Http\Router\Routing\Group\RouteGroupBuilder;
use Charcoal\Http\Router\Routing\Registry\RoutingIndex;
use Charcoal\Http\Router\Routing\Snapshot\AppRoutingSnapshot;

/**
 * Represents a collection of application routes grouped together.
 * Provides functionality for constructing and building route groups.
 */
final readonly class AppRoutes extends AbstractRouteGroup
{
    /** @var RoutingIndex */
    private RoutingIndex $registry;

    /**
     * @param \Closure(RouteGroupBuilder $group): void $declaration
     * @throws RoutingBuilderException
     */
    public function __construct(\Closure $declaration)
    {
        parent::__construct(null, "/", $declaration);
    }

    /**
     * @param RouteGroupBuilder $group
     * @return void
     * @throws RoutingBuilderException
     */
    protected function build(RouteGroupBuilder $group): void
    {
        parent::build($group);
        $this->registry = new RoutingIndex($this);
    }

    /**
     * @return RoutingIndex
     */
    public function manifest(): RoutingIndex
    {
        return $this->registry;
    }

    /**
     * Creates and returns a new instance of AppRoutingSnapshot using the current registry.
     */
    public function snapshot(): AppRoutingSnapshot
    {
        return new AppRoutingSnapshot($this->registry);
    }

    /**
     * Generates a unique identifier for a route or route group based on its path and additional chain context.
     */
    public function generateUniqueId(Route|AbstractRouteGroup $child, array $chain): string
    {
        $complete = match (true) {
            $child->path === "/" => "/",
            default => implode("/", $chain) . "/" . $child->path,
        };

        return $this->pathUniqueIdGenerator($complete,
            $child instanceof RouteGroup ? "group" : "route",
            $child instanceof Route && $child->methods ? array_keys($child->methods) : null
        );
    }

    /**
     * Generates a unique identifier based on the provided path, prefix, and HTTP methods.
     */
    private function pathUniqueIdGenerator(string $path, string $prefix, ?array $methods): string
    {
        if ($methods) {
            sort($methods, SORT_STRING);
            $methods = "@" . implode(",", $methods);
        }

        $id = sprintf("%s[%s]%s", $prefix, $path, $methods ?? "");
        return strtolower(preg_replace("/:[A-Za-z0-9_]+/", "{token}", $id));
    }
}