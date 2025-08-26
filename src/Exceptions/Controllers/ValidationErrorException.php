<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Controllers;

use Charcoal\Http\Server\Contracts\Controllers\ValidationErrorEnumInterface;

/**
 * Represents a translated validation exception that includes a specific error type,
 * an optional parameter, a custom message, and an error code. This exception is
 * typically used to handle validation errors with localized or enumerated error messages.
 * @api
 */
class ValidationErrorException extends ValidationException
{
    public function __construct(
        public readonly ValidationErrorEnumInterface $error,
        ?string                                      $param = null,
        ?string                                      $message = null,
        int                                          $code = 0
    )
    {
        parent::__construct($message ?? $error->name, $code, $param);
    }
}