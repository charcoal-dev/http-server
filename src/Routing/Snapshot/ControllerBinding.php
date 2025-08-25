<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Snapshot;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Router\Controllers\ValidatedController;
use Charcoal\Http\Router\Middleware\Bag\SealedBag;

/**
 * Represents a binding between a validated controller, entrypoint and matching path.
 * @property array<string>|true $methods If default entrypoint, true, else array of methods.
 */
final readonly class ControllerBinding
{
    public ?SealedBag $middleware;

    public function __construct(
        public ValidatedController $controller,
        public array|true          $methods,
        ?SealedBag                 $middleware,
    )
    {
        $this->middleware = $middleware->count() ? $middleware : null;
    }

    /**
     * Matches and returns the appropriate entry point based on the HTTP method and controller state.
     */
    public function matchEntryPoint(HttpMethod $method): ?string
    {
        if (!$this->controller->validated) {
            throw new \RuntimeException("Controller is not validated; Disable Tests and recompile");
        }

        if ($this->controller->defaultEntrypoint) {
            return $this->controller->defaultEntrypoint;
        }

        $verb = strtolower($method->value);
        return in_array($verb, $this->controller->entryPoints) ? $verb : null;
    }
}