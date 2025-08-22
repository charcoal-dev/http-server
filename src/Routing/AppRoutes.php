<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

use Charcoal\Http\Router\Exceptions\RoutingBuilderException;

/**
 * Represents a collection of application routes grouped together.
 * Provides functionality for constructing and building route groups.
 */
final readonly class AppRoutes extends AbstractRouteGroup
{
    /**
     * @param \Closure(RouteGroupBuilder $group): void $declaration
     * @throws RoutingBuilderException
     */
    public function __construct(\Closure $declaration)
    {
        parent::__construct(null, "/", $declaration);
    }

    protected function build(RouteGroupBuilder $group): void
    {
        parent::build($group);
        // Todo: build index
    }
}