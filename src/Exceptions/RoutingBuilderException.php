<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions;

/**
 * Represents an exception specific to errors that occur within the routing builder process.
 */
final class RoutingBuilderException extends \Exception
{
    /** @internal */
    public function __construct()
    {
        parent::__construct();
    }
}