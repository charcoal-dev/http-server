<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server;

use Charcoal\Base\Support\Helpers\UuidHelper;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Server\Config\ServerConfig;
use Charcoal\Http\Server\Enums\RequestError;
use Charcoal\Http\Server\Exceptions\PreFlightTerminateException;
use Charcoal\Http\Server\Exceptions\RequestContextException;
use Charcoal\Http\Server\Internal\ServerTestableTrait;
use Charcoal\Http\Server\Middleware\MiddlewareFacade;
use Charcoal\Http\Server\Middleware\MiddlewareRegistry;
use Charcoal\Http\Server\Request\RequestGateway;
use Charcoal\Http\Server\Request\Result\AbstractResult;
use Charcoal\Http\Server\Request\Result\ErrorResult;
use Charcoal\Http\Server\Request\Result\RedirectResult;
use Charcoal\Http\Server\Request\Result\RedirectUrl;
use Charcoal\Http\Server\Request\Result\SuccessResult;
use Charcoal\Http\Server\Request\ServerRequest;
use Charcoal\Http\Server\Routing\AppRoutes;
use Charcoal\Http\Server\Routing\Router;
use Charcoal\Http\TrustProxy\Config\ServerEnv;
use Charcoal\Http\TrustProxy\TrustGateway;

/**
 * Represents an HTTP server capable of routing requests, managing middleware,
 * and handling various stages of HTTP request processing.
 */
final class HttpServer
{
    use ServerTestableTrait;

    private readonly Router $router;
    private readonly MiddlewareRegistry $middleware;

    /**
     * @param ServerConfig $config
     * @param AppRoutes $routes
     * @param \Closure(MiddlewareRegistry): void $callback
     */
    public function __construct(
        private readonly ServerConfig $config,
        AppRoutes                     $routes,
        \Closure                      $callback
    )
    {
        $this->router = new Router($routes);
        $this->middleware = new MiddlewareRegistry();
        $callback($this->middleware);

        // Runtime (instanced & callback) registers are allowed even after lock:
        $this->middleware->lock();
    }

    /**
     * @param ServerRequest $request
     * @param ServerEnv $env
     * @return AbstractResult
     */
    public function handle(ServerRequest $request, ServerEnv $env = new ServerEnv()): AbstractResult
    {
        // Start with blank response for headers, proceed to random UUID first:
        $response = new Headers();

        try {
            $uuid = UuidHelper::uuid4();
        } catch (\Exception $e) {
            return new ErrorResult($response, RequestError::InternalError, $e);
        }

        // Placeholder until we can get the request ID from the gateway
        $response->set("X-Request-Id", $uuid);

        try {
            // Promote the request, start initial validations stage
            $requestGateway = new RequestGateway(
                $uuid,
                $response,
                $request,
                clone $this->config->requests,
                new MiddlewareFacade($this->middleware)
            );
        } catch (RequestContextException $e) {
            return new ErrorResult($response, $e->error, $e);
        }

        // Updated reference to the ServerRequest with headers
        $request = $requestGateway->request;

        // Check configured trusted proxies CIDR for the peer IP & host
        try {
            $trustProxy = TrustGateway::establishTrust($this->config->proxies, $env);
        } catch (\Exception $e) {
            return new ErrorResult($response, RequestError::BadPeerIp, $e);
        }

        $virtualHost = $this->config->matchHostname(
            strtolower(trim($trustProxy->hostname)), $trustProxy->port);
        if (!$virtualHost) {
            return new ErrorResult($response, RequestError::IncorrectHost,
                new \RuntimeException("Incorrect hostname or port"));
        }

        if ($this->config->enforceTls && $trustProxy->scheme !== "https") {
            return new RedirectResult($response,
                new RedirectUrl($request->url, 308, toggleScheme: true, absolute: true, queryStr: true));
        }

        // Update Gateway/Context with acceptance:
        $requestGateway->accepted($virtualHost, $trustProxy);

        // Match with available routes
        [$route, $matched] = $this->router->match($request->url->path);
        if (!$matched || !isset($route)) {
            return new ErrorResult($requestGateway->responseHeaders, RequestError::EndpointNotFound, null);
        }

        try {
            $entryPoint = $this->router->declaredControllersFor($route, $request->method);
        } catch (\Throwable $e) {
            $requestGateway->responseHeaders->set("Allow", implode(", ", $route->getAggregatedMethods()));
            return new ErrorResult($requestGateway->responseHeaders, RequestError::MethodNotDeclared, $e);
        }

        // Path parameters/tokens rendering:
        if ($route->params) {
            if (!isset($tokens)) $tokens = [];
            $params = array_combine($route->params, array_pad(
                array_map(fn($v) => $v[0] ?? null, $tokens),
                count($route->params),
                null
            ));
        }

        // Pre-Flight Control
        try {
            $requestGateway->preFlightCorsControl(
                $this->config->corsPolicy,
                $entryPoint[0],
                $entryPoint[1],
                $params ?? []
            );
        } catch (PreFlightTerminateException $e) {
            return match ($e->success) {
                true => new SuccessResult(204, $requestGateway->responseHeaders, null),
                default => new ErrorResult($requestGateway->responseHeaders,
                    RequestError::CorsOriginNotAllowed, null),
            };
        } catch (RequestContextException $e) {
            return new ErrorResult($requestGateway->responseHeaders, $e->error, $e);
        }

        // Todo: Init Logging
        // Todo: Concurrency Handling
        // Todo: Rate limiting
        // Todo: Authentication

        // Todo: Instantiate and Execute Controller (before Hooks)
        // Todo: Final Cleanups

        throw new \RuntimeException("Not implemented");
        //return new SuccessResult(200, $context->headers, $context->payload);
    }
}