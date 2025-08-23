<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Snapshot;

use Charcoal\Http\Router\Routing\Registry\RoutingIndex;

/**
 * Class AppRoutingSnapshot
 * @package Charcoal\Http\Router\Routing\Snapshot
 */
final readonly class AppRoutingSnapshot
{
    /** @var array<RouteSnapshot> */
    public array $routes;
    /** @var array<string, int> */
    public array $index;

    /**
     * @param RoutingIndex $index
     */
    public function __construct(RoutingIndex $index)
    {
        $inspections = [];
        $c = -1;
        foreach ($index->routes as $path => $node) {
            $c++;
            $inspections[] = new RouteSnapshot($c, $path, $node);
        }

        $this->index = array_combine(array_keys($index->routes),
            range(0, count($index->routes) - 1));
        $this->routes = $inspections;
    }

    /**
     * @param int|string $pathIndex
     * @return RouteSnapshot|null
     */
    public function inspect(int|string $pathIndex): ?RouteSnapshot
    {
        if (is_int($pathIndex)) {
            return $pathIndex > -1 && $pathIndex < count($this->index) ?
                $this->index[$pathIndex] : null;
        }

        return $this->routes[$this->index[$pathIndex] ?? null] ?? null;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->routes);
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return ["routes" => $this->routes, "index" => $this->index];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->routes = $data["routes"];
        $this->index = $data["index"];
    }
}