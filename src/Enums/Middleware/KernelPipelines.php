<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Enums\Middleware;

use Charcoal\Http\Router\Contracts\Middleware\Kernel\RequestIdResolverInterface;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\UrlEncodingEnforcerInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareEnumInterface;

/**
 * This enum provides a set of predefined constants representing
 * specific interfaces used to handle various stages or concerns
 * within the application's kernel pipeline.
 */
enum KernelPipelines: string implements MiddlewareEnumInterface
{
    case RequestID_Resolver = RequestIdResolverInterface::class;
    case URL_EncodingEnforcer = UrlEncodingEnforcerInterface::class;

    /**
     * @return array<class-string>
     */
    public static function contractsFqcn(): array
    {
        return array_map(fn(self $c) => $c->value, self::cases());
    }
}