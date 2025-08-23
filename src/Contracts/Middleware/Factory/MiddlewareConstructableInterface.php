<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Factory;

/**
 * Represents a contract for middleware classes requiring a constructor.
 */
interface MiddlewareConstructableInterface
{
    public function __construct();
}