<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Snapshot;

use Charcoal\Http\Router\Controllers\ValidatedController;
use Charcoal\Http\Router\Middleware\Bag\SealedBag;

/**
 * Represents a binding between a validated controller, entrypoint and matching path.
 * @property array<string>|true $method If default entrypoint, true, else array of methods.
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
}