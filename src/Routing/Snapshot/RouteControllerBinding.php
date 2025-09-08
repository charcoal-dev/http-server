<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Snapshot;

use Charcoal\Http\Commons\Enums\HttpMethod;

/**
 * Represents a binding between a validated controller and the associated HTTP methods.
 * This class ensures that a controller is properly associated with HTTP methods, and provides
 * functionality to determine the appropriate entry point based on the provided HTTP method.
 * @property array<HttpMethod>|true $methods If default entrypoint: bool(true)
 */
final readonly class RouteControllerBinding
{
    public function __construct(
        public ControllerAttributes $controller,
        public array|true           $methods,
    )
    {
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