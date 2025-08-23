<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Kernel;

use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;

/**
 * Represents a contract for middleware that is applied on kernel level.
 */
interface KernelMiddlewareInterface extends MiddlewareInterface
{
}