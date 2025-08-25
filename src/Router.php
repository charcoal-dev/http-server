<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router;

use Charcoal\Http\Router\Config\Config;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareTrustPolicyInterface;
use Charcoal\Http\Router\Exceptions\RequestContextException;
use Charcoal\Http\Router\Internal\RouterTestableTrait;
use Charcoal\Http\Router\Middleware\FallbackResolver;
use Charcoal\Http\Router\Middleware\Registry\RouterMiddleware;
use Charcoal\Http\Router\Request\RequestContext;
use Charcoal\Http\Router\Request\Result\AbstractResult;
use Charcoal\Http\Router\Request\Result\ErrorResult;
use Charcoal\Http\Router\Request\Result\RedirectResult;
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
     * @param Config $config
     * @param AppRoutes $routes
     * @param MiddlewareResolverInterface $resolver
     * @param MiddlewareTrustPolicyInterface|null $trustPolicy
     * @param null|\Closure(Router, AppRoutes, RouterMiddleware): void $closure
     */
    public function __construct(
        private readonly Config                          $config,
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

    /**
     * @param ServerRequest $request
     * @return AbstractResult
     */
    public function handle(ServerRequest $request): AbstractResult
    {
        $context = new RequestContext($request, $this->middleware->facade());

        // Gateway and initial Kernel pipelines (middleware)
        try {
            $context->gatewayPipelines($this->config);
        } catch (RequestContextException $e) {
            if ($e->redirectTo) {
                return new RedirectResult($context->headers, $e->redirectTo);
            }

            return new ErrorResult($context->headers, $e->error, $e);
        }


        throw new \RuntimeException("Not implemented");
        //return new SuccessResult(200, $context->headers, $context->payload);
    }
}