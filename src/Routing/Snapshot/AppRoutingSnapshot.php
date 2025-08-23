<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Snapshot;

/**
 * Represents a snapshot of the application's routing structure, allowing
 * for inspection, counting, iteration, and serialization of routes.
 * Upon instantiation, ensures that all provided route snapshots contain unique paths.
 * @implements \IteratorAggregate<RouteSnapshot>
 */
final readonly class AppRoutingSnapshot implements \IteratorAggregate, \Countable
{
    /** @var array<RouteSnapshot> */
    private array $routes;
    /** @var array<string, int> */
    private array $index;
    private int $count;

    public function __construct(RouteSnapshot ...$snapshot)
    {
        /** @var array<RouteSnapshot> $routes */
        $routes = [];
        /** @var array<string,true> $table */
        $table = [];
        foreach ($snapshot as $route) {
            if (isset($table[$route->path])) {
                throw new \UnexpectedValueException("Duplicate route: " . $route->path);
            }

            $table[$route->path] = true;
            $routes[] = $route;
        }

        $this->count = count($routes);
        $this->index = array_combine(array_keys($table), range(0, $this->count - 1));
        $this->routes = $routes;
    }

    /**
     * @param int|string $pathIndex
     * @return RouteSnapshot|null
     */
    public function inspect(int|string $pathIndex): ?RouteSnapshot
    {
        if (is_int($pathIndex)) {
            return $pathIndex > -1 && $pathIndex < count($this->routes) ?
                $this->routes[$pathIndex] : null;
        }

        return $this->routes[$this->index[$pathIndex] ?? null] ?? null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return \Traversable<RouteSnapshot>
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
        return ["routes" => $this->routes,
            "index" => $this->index,
            "count" => $this->count];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->routes = $data["routes"];
        $this->index = $data["index"];
        $this->count = $data["count"];
    }
}