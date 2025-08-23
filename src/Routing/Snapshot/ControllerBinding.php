<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Snapshot;

use Charcoal\Http\Router\Controllers\ControllerValidated;
use Charcoal\Http\Router\Middleware\Bag\SealedBag;

/**
 * Represents a binding between a validated controller, entrypoint and matching path.
 * @property array<string>|true $method If default entrypoint, true, else array of methods.
 */
final readonly class ControllerBinding
{
    public function __construct(
        public ControllerValidated $controller,
        public array|true          $method,
        public ?SealedBag          $middleware,
    )
    {
    }
}