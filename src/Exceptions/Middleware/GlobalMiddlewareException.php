<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Exceptions\Middleware;

use Charcoal\Http\Router\Exceptions\MiddlewareException;

/**
 * Represents a specific type of middleware-related exception that occurs
 * within the global middleware processing pipeline.
 */
class GlobalMiddlewareException extends MiddlewareException
{
}