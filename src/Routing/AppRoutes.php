<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

use Charcoal\Http\Router\Enums\Routing;
use Charcoal\Http\Router\Exceptions\RoutingBuilderException;
use Charcoal\Http\Router\Routing\Group\AbstractRouteGroup;
use Charcoal\Http\Router\Routing\Group\RouteGroupBuilder;
use Charcoal\Http\Router\Routing\Registry\RouteInspect;
use Charcoal\Http\Router\Routing\Registry\RoutingIndex;

/**
 * Represents a collection of application routes grouped together.
 * Provides functionality for constructing and building route groups.
 */
final readonly class AppRoutes extends AbstractRouteGroup
{
    /** @var RoutingIndex */
    private RoutingIndex $index;
    /** @var array<string, int> */
    private array $indexKeys;
    /** @var array<RouteInspect> */
    private array $inspects;

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
        $this->index = new RoutingIndex($this);
        $inspections = [];
        $c = -1;
        foreach ($this->index->routes as $path => $node) {
            $c++;
            $inspections[] = new RouteInspect($c, $path, $node);
        }

        $this->indexKeys = array_combine(array_keys($this->index->routes),
            range(0, count($this->index->routes) - 1));
        $this->inspects = $inspections;
    }

    /**
     * @param int|string $pathIndex
     * @return RouteInspect|null
     * @api
     */
    public function inspect(int|string $pathIndex): ?RouteInspect
    {
        if (is_int($pathIndex)) {
            return $pathIndex > -1 && $pathIndex < count($this->inspects) ?
                $this->inspects[$pathIndex] : null;
        }

        return $this->inspects[$this->indexKeys[$pathIndex] ?? null] ?? null;
    }

    /**
     * @return RoutingIndex
     */
    public function manifest(): RoutingIndex
    {
        return $this->index;
    }

    public function match(string $path, Routing $mode = Routing::Precise): ?RouteInspect
    {
        $canonical = $path ?? "/";
    }
}