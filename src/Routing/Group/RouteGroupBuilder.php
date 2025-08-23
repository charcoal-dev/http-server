<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Group;

use Charcoal\Http\Router\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Router\Exceptions\RoutingBuilderException;
use Charcoal\Http\Router\Routing\RouteBuilder;

/**
 * Class RouteGroupBuilder
 * @package Charcoal\Http\Router\Routing\Group
 */
final class RouteGroupBuilder
{
    protected array $children = [];

    public function __construct(protected readonly AbstractRouteGroup $group)
    {
    }

    /**
     * @param string ...$pipelines
     * @return $this
     * @api
     */
    public function pipelines(string ...$pipelines): self
    {
        $this->group->pipelines(...$pipelines);
        return $this;
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
    public function attributes(): array
    {
        return [$this->children];
    }
}