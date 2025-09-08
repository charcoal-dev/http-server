<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers;

/**
 * Interface for defining attributes associated with a controller.
 * Provides methods to configure the behavior of the attributes.
 */
interface ControllerAttributeInterface
{
    public function getBuilderFn(): \Closure;
}