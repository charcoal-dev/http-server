<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Route;

use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;

/**
 * Represents a middleware specifically tailored to be used on a per-route basis.
 */
interface RouteMiddlewareInterface extends MiddlewareInterface
{
}