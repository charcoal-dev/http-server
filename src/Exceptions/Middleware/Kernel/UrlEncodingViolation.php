<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Exceptions\Middleware\Kernel;

use Charcoal\Http\Router\Exceptions\Middleware\KernelMiddlewareException;

/**
 * Represents an exception thrown when there is a violation in URL encoding.
 */
final class UrlEncodingViolation extends KernelMiddlewareException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 400);
    }
}