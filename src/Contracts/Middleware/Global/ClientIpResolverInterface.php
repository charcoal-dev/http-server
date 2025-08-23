<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Global;

use Charcoal\Http\Commons\Headers\HeadersImmutable;

/**
 * An interface for resolving client IP addresses within the context
 * of a middleware-based router system.
 */
interface ClientIpResolverInterface extends GlobalMiddlewareInterface
{
    public function __invoke(HeadersImmutable $headers): string|false;
}