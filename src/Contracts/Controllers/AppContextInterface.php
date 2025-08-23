<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Controllers;

/**
 * Represents the application context interface, extending the base controller context interface.
 * Provides a contract for application-level context and dependencies that may be required throughout the application lifecycle.
 */
interface AppContextInterface extends ControllerContextInterface
{
}