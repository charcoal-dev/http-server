<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware;

/**
 * Represents an enumeration for middleware components.
 * Provides a method to retrieve a list of registered fully*/
interface MiddlewareEnumInterface extends \UnitEnum
{
    /**
     * @return array<class-string<MiddlewareInterface>>
     */
    public static function contractsFqcn(): array;
}