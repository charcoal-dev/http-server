<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers\Auth;

use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;

/**
 * Defines a contract for classes that act as an authentication guard,
 * responsible for handling user authentication and access control.
 * @mixin ControllerInterface
 */
interface AuthAwareControllerInterface
{
    /**
     * This method is called to pass AuthenticationContextInterface object to controller.
     */
    public function setAuthenticationContext(AuthContextInterface $authContext): void;
}
