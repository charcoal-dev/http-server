<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Internal;

use Charcoal\Http\Router\Request\GatewayEnv;

/**
 * Provides functionality to toggle test mode for an application, allowing
 * validation of controller and middleware classes to be enabled or disabled.
 * @internal
 */
trait RouterTestableTrait
{
    /**
     * @var bool Toggles controller existence check
     * @internal
     */
    public static bool $validateControllerClasses = true;
    public static bool $validateMiddlewareClasses = true;

    /**
     * Toggles the test mode for the application by enabling or disabling
     * validation of controller and middleware classes.
     */
    public static function toggleTestMode(bool $testing): void
    {
        self::$validateControllerClasses = !$testing;
        self::$validateMiddlewareClasses = !$testing;
    }

    /**
     * @var GatewayEnv|null The injected environment.
     * @internal
     */
    public static ?GatewayEnv $injectedEnvironment = null;

    /**
     * @param GatewayEnv $env
     * @return void
     */
    public static function injectEnv(GatewayEnv $env): void
    {
        if (self::$validateControllerClasses || self::$validateMiddlewareClasses) {
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