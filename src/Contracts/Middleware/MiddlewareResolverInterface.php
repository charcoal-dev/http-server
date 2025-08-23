<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware;

use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareFactoryInterface;

/**
 * Defines a contract for creating middleware components.
 * Extends the MiddlewareFactoryInterface to include specific resolver methods.
 */
interface MiddlewareResolverInterface extends MiddlewareFactoryInterface
{
    /**
     * Resolves a middleware instance based on the provided contract and context.
     */
    public function resolve(string $contract, array $context = []): ?MiddlewareInterface;
}