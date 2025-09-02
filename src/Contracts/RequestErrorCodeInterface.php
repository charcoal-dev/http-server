<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts;

/**
 * An interface defining a contract for enumerations that represent error codes.
 * It extends the built-in \UnitEnum interface to enforce that implementing enums
 * are valid UnitEnum types in PHP.
 */
interface RequestErrorCodeInterface extends \UnitEnum
{
    public function getStatusCode(): int;
}