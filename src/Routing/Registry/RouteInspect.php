<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Registry;

use Charcoal\Http\Router\Routing\Group\AbstractRouteGroup;
use Charcoal\Http\Router\Routing\Route;

/**
 * Represents inspection details of a route or route group.
 * This class provides metadata about a route or route group, including its
 * path, type, associated methods, and grouping namespace if applicable.
 */
final readonly class RouteInspect
{
    public bool $isGroup;
    public bool $isController;
    public ?array $groupNamespace;
    public ?array $methods;

    public function __construct(public int $index, public string $path, array $node)
    {
        $isGroup = false;
        $isRoute = false;
        $groupNamespace = [];
        $methods = [];
        foreach ($node as $leaf) {
            if ($leaf instanceof AbstractRouteGroup) {
                $isGroup = true;
                if ($leaf->namespace) {
                    $groupNamespace[] = $leaf->namespace;
                }

                continue;
            }

            if ($leaf instanceof Route) {
                $isRoute = true;
                foreach ($leaf->methods ?: ["*" => true] as $method => $bool) {
                    $methods[$method] = $leaf->classname;
                }
            }
        }

        $this->isGroup = $isGroup;
        $this->isController = $isRoute;
        $this->groupNamespace = $groupNamespace ?: null;
        $this->methods = $methods ?: null;
    }
}