<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Router\Contracts\Middleware\RouteMiddlewareInterface;
use Charcoal\Http\Router\Support\HttpMethods;

/**
 * Represents a route builder for creating route configurations.
 */
final class RouteBuilder
{
    /** @var HttpMethods|null */
    protected ?HttpMethods $methods = null;
    /** @var list<string|RouteMiddlewareInterface> */
    protected array $pipelines = [];

    public function __construct(
        public readonly string $path,
        public readonly string $classname,
        public readonly bool   $checkClass = true,
    )
    {
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
    public function pipelines(string|RouteMiddlewareInterface ...$pipelines): self
    {
        $this->pipelines[] = $pipelines;
        return $this;
    }

    /**
     * @return array{0: ?HttpMethods, 1: list<string|RouteMiddlewareInterface>}
     */
    public function attributes(): array
    {
        return [$this->methods, $this->pipelines ?? []];
    }
}