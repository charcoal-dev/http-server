<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Registry;

use Charcoal\Http\Router\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Router\Controllers\ControllerValidated;
use Charcoal\Http\Router\Internal\Constants;
use Charcoal\Http\Router\Router;
use Charcoal\Http\Router\Routing\AppRoutes;
use Charcoal\Http\Router\Routing\Group\AbstractRouteGroup;
use Charcoal\Http\Router\Routing\Route;
use Charcoal\Http\Router\Routing\Snapshot\AppRoutingSnapshot;
use Charcoal\Http\Router\Routing\Snapshot\ControllerBinding;
use Charcoal\Http\Router\Routing\Snapshot\RouteSnapshot;

/**
 * Represents a consolidated view of application routes.
 * Provides utilities to handle and organize routes into a structured format.
 */
final readonly class ConsolidatedRoutes
{
    /** @var array<string, list<Route|AbstractRouteGroup>> */
    public array $declared;
    /** @var array<class-string<ControllerInterface>,ControllerValidated> */
    public array $controllers;
    /** @var array<string,<array<string,class-string<ControllerInterface>>> */
    public array $entryPoints;

    /**
     * @param AbstractRouteGroup $group
     * @return array
     */
    public static function createFor(AbstractRouteGroup $group): array
    {
        return self::create($group);
    }

    /**
     * @param AppRoutes $routes
     */
    public function __construct(AppRoutes $routes)
    {
        $this->declared = self::createFor($routes);

        // Get consolidated routes
        /** @var array<string,<array<string,class-string<ControllerInterface>>> $routeMap */
        $routeMap = [];
        /** @var array<class-string<ControllerInterface>,array<string>> $controllers */
        $controllers = [];
        foreach ($this->declared as $path => $routes) {
            foreach ($routes as $route) {
                if (!$route instanceof Route) {
                    continue;
                }

                if (!isset($routeMap[$path])) {
                    $routeMap[$path] = [];
                }

                if (!isset($controllers[$route->classname])) {
                    $controllers[$route->classname] = [];
                }

                $methods = array_map(fn($m) => $m->name ?? [], $route->methods?->getArray() ?? []);

                // Append to be validated controller entryPoints
                $controllers[$route->classname] = [...$controllers[$route->classname], ...$methods];

                // Set the wildcard method for the route map
                if (!$methods) {
                    $methods = [Constants::METHOD_ANY];
                }

                // Don't allow duplicate methods+paths
                foreach ($methods as $method) {
                    // Deduplicate routes
                    if (isset($routeMap[$path][$method])) {
                        throw new \InvalidArgumentException("Duplicate route: " . $path . " " . $method);
                    }

                    $routeMap[$path][$method] = $route->classname;
                }
            }
        }

        // All entrypoint are now known!
        $validatedControllers = [];
        foreach ($controllers as $classname => $methods) {
            if (isset($validatedControllers[$classname])) {
                continue;
            }

            $validatedControllers[$classname] = new ControllerValidated(
                $classname,
                $methods,
                Router::$checkControllerExists
            );
        }

        // Controllers are now validated!
        $this->controllers = $validatedControllers;
        $this->entryPoints = $routeMap;
    }

    /**
     * Creates a snapshot of the currently declared routes along with their bindings.
     *
     * @return AppRoutingSnapshot The snapshot containing all route paths and their associated controller bindings.
     */
    public function snapshot(): AppRoutingSnapshot
    {
        // Create a final binding map
        $snapshot = [];
        foreach ($this->declared as $path => $routes) {
            /** @var array<ControllerBinding> $routeMap */
            $pathBindings = [];
            foreach ($routes as $route) {
                if (!$route instanceof Route) {
                    continue;
                }

                $pathBindings[] = new ControllerBinding(
                    $route->classname,
                    $route->methods?->getArray() ?? true,
                    $route->middleware
                );
            }

            $snapshot[] = new RouteSnapshot($path, ...$pathBindings);
        }

        return new AppRoutingSnapshot(...$snapshot);
    }

    /**
     * array<string, list<Route|AbstractRouteGroup>>
     */
    private static function create(AbstractRouteGroup $group): array
    {
        $out = [];
        self::collect($group, $group->path, $out);
        return $out;
    }

    /**
     * @param AbstractRouteGroup $group
     * @param string $parent
     * @param array $out
     * @return void
     */
    private static function collect(AbstractRouteGroup $group, string $parent, array &$out): void
    {
        $out[$parent] ??= [];
        $out[$parent][] = $group;
        foreach ($group->children as $child) {
            $prefix = self::concat($parent, $child->path);
            if ($child instanceof AbstractRouteGroup) {
                self::collect($child, $prefix, $out);
            } else {
                $out[$prefix] ??= [];
                $out[$prefix][] = $child;
            }
        }
    }

    /**
     * @param string $a
     * @param string $b
     * @return string
     */
    private static function concat(string $a, string $b): string
    {
        $a = trim($a, "/");
        $b = trim($b, "/");
        return (($a ? ("/" . $a) : "") . (($b ? ("/" . $b) : ""))) ?: "/";
    }
}