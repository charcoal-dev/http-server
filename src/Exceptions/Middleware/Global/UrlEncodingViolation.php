<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Exceptions\Middleware\Global;

use Charcoal\Http\Router\Exceptions\Middleware\GlobalMiddlewareException;

/**
 * Represents an exception thrown when there is a violation in URL encoding.
 */
class UrlEncodingViolation extends GlobalMiddlewareException
{
}