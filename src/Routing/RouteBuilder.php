<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Router\Enums\Middleware\Scope;
use Charcoal\Http\Router\Middleware\MiddlewareBag;
use Charcoal\Http\Router\Support\HttpMethods;

/**
 * Represents a route builder for creating route configurations.
 */
final class RouteBuilder
{
    /** @var HttpMethods|null */
    protected ?HttpMethods $methods = null;
    private MiddlewareBag $middleware;

    public function __construct(
        public readonly string $path,
        public readonly string $classname
    )
    {
        $this->middleware = new MiddlewareBag(Scope::Route);
    }

    /**
     * @api
     */
    public function methods(HttpMethod ...$methods): self
    {
        $this->methods = new HttpMethods(...$methods);
        return $this;
    }

    /**
     * @api
     */
    public function pipelines(string ...$pipelines): self
    {
        $this->middleware->set(...$pipelines);
        return $this;
    }

    /**
     * @return array{0: ?HttpMethods, 1: MiddlewareBag}
     * @internal
     */
    public function attributes(): array
    {
        return [$this->methods, $this->middleware];
    }
}