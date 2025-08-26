<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Internal;

use Charcoal\Http\Server\TrustProxy\ServerEnv;

/**
 * Provides functionality for toggling test mode in the application and managing
 * an injected server environment.
 */
trait ServerTestableTrait
{
    /**
     * @var bool Toggles controller existence check
     * @internal
     */
    public static bool $validateControllerClasses = true;

    /**
     * Toggles the test mode for the application by enabling or disabling
     * validation of controller and middleware classes.
     */
    public static function toggleTestMode(bool $testing): void
    {
        self::$validateControllerClasses = !$testing;
    }

    /**
     * @var ServerEnv|null The injected environment.
     * @internal
     */
    public static ?ServerEnv $injectedEnvironment = null;

    /**
     * @param ServerEnv $env
     * @return void
     */
    public static function injectEnv(ServerEnv $env): void
    {
        if (self::$validateControllerClasses) {
            throw new \LogicException("Cannot inject environment while NOT in test mode");
        }

        self::$injectedEnvironment = $env;
    }

    /**
     * Resets the environment by clearing the current injected environment.
     */
    public static function resetEnv(): void
    {
        self::$injectedEnvironment = null;
    }
}