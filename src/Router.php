<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router;

use Charcoal\Http\Router\Contracts\Middleware\MiddlewareResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareTrustPolicyInterface;
use Charcoal\Http\Router\Internal\RouterTestableTrait;
use Charcoal\Http\Router\Middleware\FallbackResolver;
use Charcoal\Http\Router\Middleware\Registry\RouterMiddleware;
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
    private readonly RouterMiddleware $middleware;

    /**
     * @param AppRoutes $routes
     * @param MiddlewareResolverInterface $resolver
     * @param MiddlewareTrustPolicyInterface|null $trustPolicy
     * @param null|\Closure(Router, AppRoutes, RouterMiddleware): void $closure
     */
    public function __construct(
        AppRoutes                                        $routes,
        MiddlewareResolverInterface                      $resolver = new FallbackResolver(),
        private readonly ?MiddlewareTrustPolicyInterface $trustPolicy = null,
        ?\Closure                                        $closure = null
    )
    {
        $this->routes = $routes->snapshot();
        $this->middleware = new RouterMiddleware($this->routes, $resolver, $this->trustPolicy);
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
        $processor = new RequestContext($request, $this->middleware->facade());
    }
}