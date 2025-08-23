<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router;

use Charcoal\Http\Router\Contracts\Middleware\MiddlewareResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareTrustPolicyInterface;
use Charcoal\Http\Router\Internal\MiddlewareRegistry;
use Charcoal\Http\Router\Internal\RouterTestableTrait;
use Charcoal\Http\Router\Middleware\FallbackResolver;
use Charcoal\Http\Router\Request\RequestContext;
use Charcoal\Http\Router\Request\ServerRequest;
use Charcoal\Http\Router\Routing\AppRoutes;
use Charcoal\Http\Router\Routing\Snapshot\AppRoutingSnapshot;

/**
 * Represents a router responsible for handling application routing and middleware pipelines.
 */
final class Router
{
    use RouterTestableTrait;

    private readonly AppRoutingSnapshot $routes;
    private readonly MiddlewareRegistry $middleware;

    /**
     * @param AppRoutes $routes
     * @param MiddlewareResolverInterface $resolver
     * @param MiddlewareTrustPolicyInterface|null $trustPolicy
     * @param null|\Closure(Router, AppRoutes, MiddlewareRegistry): void $closure
     */
    public function __construct(
        AppRoutes                                          $routes,
        MiddlewareResolverInterface                        $resolver = new FallbackResolver(),
        protected readonly ?MiddlewareTrustPolicyInterface $trustPolicy = null,
        ?\Closure                                          $closure = null
    )
    {
        $this->routes = $routes->snapshot();
        $this->middleware = new MiddlewareRegistry($this->routes, $resolver, $this->trustPolicy);
        if ($closure) {
            // For final inspections, event capturing, logs...
            $closure($this, $routes, $this->middleware);
        }
    }

    /**
     * Retrieves the current routing snapshot.
     * @return AppRoutingSnapshot
     * @api
     */
    public function routes(): AppRoutingSnapshot
    {
        return $this->routes;
    }

    public function handle(ServerRequest $request): void
    {
        $processor = new RequestContext($this, $request);
    }
}