<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

/**
 * Class CascadingRouteMap
 * @package Charcoal\Http\Router\Routing
 */
final readonly class RoutingIndex
{
    /** @var array<string, list<Route|AbstractRouteGroup>> */
    public array $routes;

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
        $this->routes = self::createFor($routes);
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