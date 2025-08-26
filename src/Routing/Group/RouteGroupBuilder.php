<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Group;

use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Server\Exceptions\RoutingBuilderException;
use Charcoal\Http\Server\Routing\RouteBuilder;

/**
 * This class facilitates the building of route groups and allows for defining pipelines, individual routes,
 * and nested route groups within a structured routing system. It provides methods to configure middleware pipelines,
 * create routes, and define nested route groups.
 */
final class RouteGroupBuilder
{
    /** @var array{1: list<RouteBuilder|RouteGroup>} */
    protected array $children = [];

    public function __construct(protected readonly AbstractRouteGroup $group)
    {
    }

    /**
     * @param string $path
     * @param class-string<ControllerInterface> $classname
     * @return RouteBuilder
     */
    public function route(string $path, string $classname): RouteBuilder
    {
        return $this->children[] = new RouteBuilder($path, $classname);
    }

    /**
     * @param string $path
     * @param \Closure(RouteGroupBuilder $group): void $declaration
     * @return RouteGroup
     * @throws RoutingBuilderException
     */
    public function group(string $path, \Closure $declaration): RouteGroup
    {
        return $this->children[] = new RouteGroup($this->group, $path, $declaration);
    }

    /**
     * @return array{1: list<RouteBuilder|RouteGroup>}
     * @internal
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}