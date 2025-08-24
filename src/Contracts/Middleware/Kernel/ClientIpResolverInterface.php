<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Kernel;

use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareConstructableInterface;

/**
 * An interface for resolving client IP addresses within the context
 * of a middleware-based router system.
 */
interface ClientIpResolverInterface extends KernelMiddlewareInterface, MiddlewareConstructableInterface
{
    public function __invoke(HeadersImmutable $headers): string;
}