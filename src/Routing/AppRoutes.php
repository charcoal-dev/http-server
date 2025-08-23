<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

use Charcoal\Http\Router\Exceptions\RoutingBuilderException;
use Charcoal\Http\Router\Routing\Group\AbstractRouteGroup;
use Charcoal\Http\Router\Routing\Group\RouteGroupBuilder;
use Charcoal\Http\Router\Routing\Registry\ConsolidatedRoutes;
use Charcoal\Http\Router\Routing\Snapshot\AppRoutingSnapshot;

/**
 * Represents a collection of application routes grouped together.
 * Provides functionality for constructing and building route groups.
 */
final readonly class AppRoutes extends AbstractRouteGroup
{
    private ConsolidatedRoutes $compiled;

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
        $this->compiled = new ConsolidatedRoutes($this);
    }

    /**
     * @return ConsolidatedRoutes
     */
    public function inspect(): ConsolidatedRoutes
    {
        return $this->compiled;
    }

    /**
     * Creates and returns a new instance of AppRoutingSnapshot using the current registry.
     */
    public function snapshot(): AppRoutingSnapshot
    {
        return $this->compiled->snapshot();
    }
}