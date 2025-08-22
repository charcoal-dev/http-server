<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Group;

use Charcoal\Http\Router\Contracts\Middleware\GroupMiddlewareInterface;
use Charcoal\Http\Router\Controller\AbstractController;
use Charcoal\Http\Router\Exceptions\RoutingBuilderException;
use Charcoal\Http\Router\Routing\RouteBuilder;

/**
 * Class RouteGroupBuilder
 * @package Charcoal\Http\Router\Routing\Group
 */
final class RouteGroupBuilder
{
    protected ?string $namespace = null;
    protected array $children = [];
    protected array $pipelines = [];

    public function __construct(protected readonly AbstractRouteGroup $group)
    {
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function namespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @param string|GroupMiddlewareInterface ...$pipelines
     * @return $this
     * @api
     */
    public function pipelines(string|GroupMiddlewareInterface ...$pipelines): self
    {
        $this->pipelines[] = $pipelines;
        return $this;
    }

    /**
     * @param string $path
     * @param class-string<AbstractController> $classname
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
     * @return array{0: ?string, 1: list<RouteBuilder|RouteGroup>, 2: list<string|GroupMiddlewareInterface>}
     * @internal
     */
    public function attributes(): array
    {
        return [$this->namespace, $this->children, $this->pipelines];
    }
}