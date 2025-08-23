<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Enums\Middleware;

use Charcoal\Http\Router\Contracts\Middleware\Kernel\ClientIpResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\RequestIdResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\UrlEncodingEnforcerInterface;

/**
 * Enumeration representing the scope at which middleware can be applied.
 */
enum Scope
{
    case Kernel;
    case Group;
    case Route;

    public function getRegisteredPipelines(): array
    {
        return match ($this) {
            Scope::Kernel => [
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