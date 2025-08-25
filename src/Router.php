<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Router\Config\RouterConfig;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareTrustPolicyInterface;
use Charcoal\Http\Router\Controllers\ValidatedController;
use Charcoal\Http\Router\Enums\RequestError;
use Charcoal\Http\Router\Exceptions\HttpOptionsException;
use Charcoal\Http\Router\Exceptions\RequestContextException;
use Charcoal\Http\Router\Internal\RouterTestableTrait;
use Charcoal\Http\Router\Middleware\FallbackResolver;
use Charcoal\Http\Router\Middleware\Registry\RouterMiddleware;
use Charcoal\Http\Router\Request\RequestContext;
use Charcoal\Http\Router\Request\Result\AbstractResult;
use Charcoal\Http\Router\Request\Result\ErrorResult;
use Charcoal\Http\Router\Request\Result\OptionsResult;
use Charcoal\Http\Router\Request\Result\RedirectResult;
use Charcoal\Http\Router\Request\ServerRequest;
use Charcoal\Http\Router\Routing\AppRoutes;
use Charcoal\Http\Router\Routing\Snapshot\AppRoutingSnapshot;
use Charcoal\Http\Router\Routing\Snapshot\ControllerBinding;
use Charcoal\Http\Router\Routing\Snapshot\RouteSnapshot;

/**
 * Represents a router responsible for handling application routing and middleware pipelines.
 */
final class Router
{
    use RouterTestableTrait;

    private readonly AppRoutingSnapshot $routes;
    private readonly RouterMiddleware $middleware;

    /**
     * @param RouterConfig $config
     * @param AppRoutes $routes
     * @param MiddlewareResolverInterface $resolver
     * @param MiddlewareTrustPolicyInterface|null $trustPolicy
     * @param null|\Closure(Router, AppRoutes, RouterMiddleware): void $closure
     */
    public function __construct(
        private readonly RouterConfig                    $config,
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

        // Match with available routes
        $matched = false;
        foreach ($this->routes as $route) {
            if (preg_match($route->matchRegExp, $request->url->path) === 1) {
                $matched = true;
                break;
            }
        }

        if (!$matched || !isset($route)) {
            return new ErrorResult($context->headers, RequestError::EndpointNotFound, null);
        }

        try {
            $entryPoint = $this->resolveControllerEntryPoint($route, $request->method);
        } catch (\Throwable $e) {
            return new ErrorResult($context->headers, RequestError::MethodNotDeclared, $e);
        }

        // Pre-Flight Control
        try {
            $context->preFlightControl();
        } catch (HttpOptionsException $e) {
            return new OptionsResult(204, $e->allowedOrigin, $e->corsPolicy, $context->headers);
        } catch (RequestContextException $e) {
            return new ErrorResult($context->headers, $e->error, $e);
        }

        throw new \RuntimeException("Not implemented");
        //return new SuccessResult(200, $context->headers, $context->payload);
    }

    /**
     * Resolves and returns the appropriate controller binding for the given route and HTTP method.
     * @return null|array<ControllerBinding,non-empty-string>
     */
    private function resolveControllerEntryPoint(RouteSnapshot $route, HttpMethod $method): ?array
    {
        $defaultController = null;
        $matchedController = null;
        foreach ($route->controllers as $controller) {
            if ($controller->methods === true) {
                $defaultController = $controller;
                continue;
            }

            if (is_array($controller->methods) && in_array($method->value, $controller->methods)) {
                $matchedController = $controller;
                break;
            }
        }

        $controller = $matchedController ?? $defaultController ?? null;
        if (!$controller) {
            throw new \RuntimeException("No controller resolved with HTTP method: " . $method->value);
        }

        $entryPoint = $controller->matchEntryPoint($method);
        if (!$entryPoint) {
            throw new \RuntimeException(sprintf("Method %s not declared in: %s",
                $method->name, $controller->controller->classname));
        }

        return [$controller, $entryPoint];
    }
}