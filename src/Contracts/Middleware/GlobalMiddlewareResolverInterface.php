<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware;

use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareFactoryInterface;
use Charcoal\Http\Router\Contracts\Middleware\Global\ClientIpResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\Global\RequestIdResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\Global\UrlEncodingEnforcerInterface;

/**
 * Defines a contract for creating global middleware components.
 * Extends the MiddlewareFactoryInterface to include specific resolver methods.
 */
interface GlobalMiddlewareResolverInterface extends MiddlewareFactoryInterface
{
    public function resolveRequestIdResolver(): RequestIdResolverInterface;

    public function resolveUrlEncodingEnforcer(): UrlEncodingEnforcerInterface;

    public function resolveClientIpResolver(): ClientIpResolverInterface;
}