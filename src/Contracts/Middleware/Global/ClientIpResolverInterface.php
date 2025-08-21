<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Global;

use Charcoal\Http\Router\Contracts\Middleware\RouterMiddlewareInterface;

/**
 * An interface for resolving client IP addresses within the context
 * of a middleware-based router system.
 */
interface ClientIpResolverInterface extends RouterMiddlewareInterface
{
    public function resolveIpAddress(): string;
}