<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Kernel;

use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareFactoryInterface;
use Charcoal\Http\Router\Request\CorsPolicy;

/**
 * Defines the contract for resolving Cross-Origin Resource Sharing (CORS) policies.
 * This interface extends KernelMiddlewareInterface and MiddlewareFactoryInterface
 * to integrate middleware handling with CORS policy resolution.
 */
interface CorsPolicyResolverInterface extends KernelMiddlewareInterface, MiddlewareFactoryInterface
{
    public function __invoke(): CorsPolicy;
}