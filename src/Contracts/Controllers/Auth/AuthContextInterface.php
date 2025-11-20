<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers\Auth;

/**
 * Marker for objects that are passed to controllers on successful authentication
 */
interface AuthContextInterface
{
    /**
     * Return unique identifier for an authenticated client,
     * This value maybe used to replace IP address in various operations
     */
    public function getAuthenticatedId(): string;
}