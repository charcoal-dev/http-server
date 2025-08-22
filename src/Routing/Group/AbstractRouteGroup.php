<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Group;

use Charcoal\Http\Router\Exceptions\RoutingBuilderException;
use Charcoal\Http\Router\Routing\Route;
use Charcoal\Http\Router\Routing\RouteBuilder;

/**
 * Abstract class that represents a group of routes.
 * Provides functionality to define namespaces, paths, and manage child routes
 * or route groups within a larger routing structure.
 */
abstract readonly class AbstractRouteGroup
{
    public string $path;
    public ?string $namespace;
    public array $children;

    /**
     * @param RouteGroup|null $parent
     * @param string $path
     * @param \Closure(RouteGroupBuilder $group): void $declaration
     * @throws RoutingBuilderException
     */
    public function __construct(
        ?AbstractRouteGroup $parent,
        string              $path,
        \Closure            $declaration,
    )
    {
        $path = trim($path, "/");
        $this->path = match (true) {
            !$parent && $path === "" => "/",
            !$parent && $path => throw new \InvalidArgumentException("Root route must be '/' precise"),
            $path && preg_match('/^(?:[A-Za-z0-9_.-]*[A-Za-z0-9]|:[A-Za-z0-9]+)(?:\/(?:[A-Za-z0-9_.-]*[A-Za-z0-9]|:[A-Za-z0-9]+))*$/', $path) => $path,
            default => throw new \InvalidArgumentException("Route prefix is invalid " . $path),
        };

        $groupPolicy = new RouteGroupBuilder($this);
        $declaration($groupPolicy);
        $this->build($groupPolicy);
    }

    /**
     * @throws RoutingBuilderException
     */
    protected function build(RouteGroupBuilder $group): void
    {
        $children = [];
        $groupPolicies = $group->attributes();

        // Namespace?
        $namespace = $groupPolicies[0];
        if (!is_null($namespace)) {
            if (!preg_match('/^[A-Za-z0-9_]+(\\\\[A-Za-z0-9_]+)*(\\\\\*)$/', $namespace)) {
                throw new \InvalidArgumentException("Namespace contains an illegal character");
            }
        }

        $this->namespace = $namespace;

        // Children
        $tracker = [];
        $num = 0;
        foreach ($groupPolicies[1] as $child) {
            $num++;
            try {
                if ($child instanceof RouteBuilder) {
                    $routePolicies = $child->attributes();
                    $route = new Route($child->path, $child->classname, $routePolicies[0]);
                    $this->appendChild($children, $tracker, $route);
                }

                if ($child instanceof RouteGroup) {
                    $this->appendChild($children, $tracker, $child);
                }
            } catch (\Throwable $t) {
                throw new RoutingBuilderException("Group [" . $this->path . "][#" . $num . "]: " .
                    $t->getMessage(), previous: $t);
            }
        }

        $this->children = $children;
    }

    /**
     * @param Route|RouteGroup $child
     * @return string
     */
    private function generatePseudoId(Route|RouteGroup $child): string
    {
        // Methods are already canonicalized when stored in Route instance
        $id = sprintf("[%s][%s]%s", $this->path, $child->path,
            $child instanceof Route && $child->methods ? "@" . implode(",", array_keys($child->methods)) : "");
        return strtolower(preg_replace("/:[A-Za-z0-9_]+/", "{token}", $id));
    }

    /**
     * @param array $children
     * @param array $tracker
     * @param Route|RouteGroup $child
     * @return void
     */
    private function appendChild(array &$children, array &$tracker, Route|RouteGroup $child): void
    {
        $pseudoId = $this->generatePseudoId($child);
        if (isset($tracker[$pseudoId])) {
            throw new \OutOfBoundsException("Duplicate route path: " . $child->path);
        }

        $tracker[$pseudoId] = true;
        $children[] = $child;
    }
}