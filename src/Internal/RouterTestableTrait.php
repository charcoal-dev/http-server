<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Internal;

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
}