<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing;

use Charcoal\Http\Server\Exceptions\RoutingBuilderException;
use Charcoal\Http\Server\Routing\Group\AbstractRouteGroup;
use Charcoal\Http\Server\Routing\Group\RouteGroupBuilder;
use Charcoal\Http\Server\Routing\Registry\ConsolidatedRoutes;
use Charcoal\Http\Server\Routing\Snapshot\RoutingSnapshot;

/**
 * Represents a collection of application routes grouped together.
 * Provides functionality for constructing and building route groups.
 */
readonly class HttpRoutes extends AbstractRouteGroup
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
    final protected function build(RouteGroupBuilder $group): void
    {
        parent::build($group);
        $this->compiled = new ConsolidatedRoutes($this);
    }

    /**
     * @return ConsolidatedRoutes
     */
    final public function inspect(): ConsolidatedRoutes
    {
        return $this->compiled;
    }

    /**
     * Creates and returns a new instance of AppRoutingSnapshot using the current registry.
     */
    final public function snapshot(): RoutingSnapshot
    {
        return $this->compiled->snapshot();
    }
}