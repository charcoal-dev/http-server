<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Controllers;

use Charcoal\Contracts\Sapi\ValidationExceptionInterface;

/**
 * This exception includes an optional parameter name associated with the validation failure,
 * along with a message describing the error and an optional error code.
 */
class ValidationException extends \Exception implements ValidationExceptionInterface
{
    public function __construct(
        string                $message,
        int                   $code = 0,
        public readonly array $context = []
    )
    {
        parent::__construct($message, $code);
    }
}