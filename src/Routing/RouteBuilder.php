<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Support\HttpMethods;

/**
 * Represents a route builder for creating route configurations.
 */
final class RouteBuilder
{
    protected ?HttpMethods $methods = null;

    public function __construct(
        public readonly string $path,
        public readonly string $classname
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
     * @return HttpMethods|null
     * @internal
     */
    public function getMethods(): ?HttpMethods
    {
        return $this->methods;
    }
}