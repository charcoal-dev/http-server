<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Enums\Middleware;

use Charcoal\Http\Router\Contracts\Middleware\Global\ClientIpResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\Global\RequestIdResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\Global\UrlEncodingEnforcerInterface;

/**
 * Enumeration representing the scope at which middleware can be applied.
 */
enum Scope
{
    case Global;
    case Group;
    case Route;

    public function getRegisteredPipelines(): array
    {
        return match ($this) {
            Scope::Global => [
                RequestIdResolverInterface::class,
                UrlEncodingEnforcerInterface::class,
                ClientIpResolverInterface::class,
            ],
            Scope::Group,
            Scope::Route => [
            ],
        };
    }
}