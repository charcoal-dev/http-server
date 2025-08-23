<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router;

use Charcoal\Http\Router\Contracts\Middleware\Kernel\KernelMiddlewareInterface;
use Charcoal\Http\Router\Internal\RouterTestableTrait;
use Charcoal\Http\Router\Middleware\MiddlewareRegistry;
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

    public readonly AppRoutingSnapshot $snapshot;

    public function __construct(
        AppRoutes $routes,
        MiddlewareRegistry $middlewareRegistry
    )
    {
        $this->snapshot = $routes->snapshot();
    }

    public function middleware(KernelMiddlewareInterface ...$pipelines): self
    {
        return $this;
    }

    public function accept(ServerRequest $request): void
    {
        $processor = new RequestContext($this, $request);
    }

    public function pipelines()
    {

    }
}