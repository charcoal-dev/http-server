<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware;

/**
 * Defines a contract for middleware that can be used in a group context.
 * Extends the functionality of the base MiddlewareInterface.
 */
interface GroupMiddlewareInterface extends MiddlewareInterface
{
}