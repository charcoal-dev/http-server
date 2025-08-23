<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Factory;

/**
 * Provides a method to create a middleware instance with the given options.
 */
interface MiddlewareFactoryInterface
{
    public static function create(array $options = []): static;
}