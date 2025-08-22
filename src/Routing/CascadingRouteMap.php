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
class CascadingRouteMap
{
    public function __construct()
    {
    }

    public function __invoke(AbstractRouteGroup $group): array
    {
        $built = $this->build($group);
        ksort($built, SORT_STRING);
        return $built;
    }

    /**
     * @param AbstractRouteGroup $group
     * array<string, list<Route|AbstractGroup>>
     */
    private function build(AbstractRouteGroup $group): array
    {
        $out = [];
        $this->collect($group, $group->path, $out);
        return $out;
    }

    private function collect(AbstractRouteGroup $group, string $parent, array &$out): void
    {
        $out[$parent] ??= [];
        $out[$parent][] = $group;
        foreach ($group->children as $child) {
            $prefix = $this->concat($parent, $child->path);
            if ($child instanceof AbstractRouteGroup) {
                $this->collect($child, $prefix, $out);
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
    private function concat(string $a, string $b): string
    {
        $a = trim($a, "/");
        $b = trim($b, "/");
        return (($a ? ("/" . $a) : "") . (($b ? ("/" . $b) : ""))) ?: "/";
    }
}