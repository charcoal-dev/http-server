<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Global;

use Charcoal\Http\Router\Contracts\Middleware\Global\ClientIpResolverInterface;

/**
 * Provides functionality to resolve the IP address of the client making the request.
 * Implements the ClientIpResolverInterface to ensure consistency in implementation.
 */
class ClientIpResolver implements ClientIpResolverInterface
{
    /**
     * Resolves and returns the client's IP address from the server variables.
     */
    public function resolveIpAddress(): string
    {
        return $_SERVER["REMOTE_ADDR"] ?:
            throw new \RuntimeException("Unable to resolve client IP address");
    }
}