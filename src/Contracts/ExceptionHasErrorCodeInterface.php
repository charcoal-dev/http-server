<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts;

/**
 * An interface representing exceptions that provide an associated error code.
 * This ensures that any exception implementing this interface will have
 * a method to retrieve an instance of RequestErrorCodeInterface, representing
 * a specific error code related to the exception.
 */
interface ExceptionHasErrorCodeInterface extends \Throwable
{
    public function errorCode(): \UnitEnum;
}