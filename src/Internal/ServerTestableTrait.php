<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Internal;

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
}