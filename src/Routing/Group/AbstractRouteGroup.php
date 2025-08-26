<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Group;

use Charcoal\Http\Server\Exceptions\RoutingBuilderException;
use Charcoal\Http\Server\Internal\Constants;
use Charcoal\Http\Server\Routing\Registry\Route;
use Charcoal\Http\Server\Routing\RouteBuilder;

/**
 * Abstract class that represents a group of routes.
 * Provides functionality to define namespaces, paths, and manage child routes
 * or route groups within a larger routing structure.
 */
abstract readonly class AbstractRouteGroup
{
    public string $path;
    public array $children;

    /**
     * @param RouteGroup|null $parent
     * @param string $path
     * @param \Closure(RouteGroupBuilder $group): void $declaration
     * @throws RoutingBuilderException
     */
    public function __construct(
        protected ?AbstractRouteGroup $parent,
        string                        $path,
        \Closure                      $declaration,
    )
    {
        $path = trim($path, "/");
        $this->path = match (true) {
            !$parent && $path === "" => "/",
            !$parent && $path => throw new \InvalidArgumentException("Root route must be '/' precise"),
            $path && preg_match(Constants::PATH_VALIDATION_REGEXP, $path) => $path,
            default => throw new \InvalidArgumentException("Route prefix is invalid " . $path),
        };

        try {
            $groupPolicy = new RouteGroupBuilder($this);
            $declaration($groupPolicy);
            $this->build($groupPolicy);
        } catch (RoutingBuilderException $e) {
            throw $e;
        } catch (\Throwable $t) {
            throw new RoutingBuilderException("Group [" . $this->path . "]: " . $t->getMessage(), previous: $t);
        }
    }

    /**
     * @throws RoutingBuilderException
     */
    protected function build(RouteGroupBuilder $group): void
    {
        $children = [];
        $groupChildren = $group->getChildren();

        // Children
        $num = 0;
        foreach ($groupChildren as $child) {
            $num++;
            try {
                if (!$child instanceof RouteBuilder && !$child instanceof RouteGroup) {
                    throw new \UnexpectedValueException("Unsupported child element: " . get_debug_type($child));
                }

                if ($child instanceof RouteBuilder) {
                    $route = new Route($child->path, $child->classname, $child->getMethods());
                    $this->appendChild($children, $route);
                }

                if ($child instanceof RouteGroup) {
                    $this->appendChild($children, $child);
                }
            } catch (\Throwable $t) {
                throw new RoutingBuilderException("Group [" . $this->path . "][#" . $num . "]: " .
                    $t->getMessage(), previous: $t);
            }
        }

        $this->children = $children;
    }

    /**
     * @param array $children
     * @param Route|RouteGroup $child
     * @return void
     */
    private function appendChild(array &$children, Route|RouteGroup $child): void
    {
        $children[] = $child;
    }
}