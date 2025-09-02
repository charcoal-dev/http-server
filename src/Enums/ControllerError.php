<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

use Charcoal\Http\Server\Contracts\RequestErrorCodeInterface;

/**
 * Implements the StatusCodeFromErrorInterface to allow retrieving the corresponding HTTP status code
 * for each error type. This is useful for mapping internal errors to appropriate HTTP response codes.
 */
enum ControllerError implements RequestErrorCodeInterface
{
    case ExecutionFlow;
    case UnrecognizedParam;

    /** @for= Domain/User-end Triggered */
    case ValidationException;

    public function getStatusCode(): int
    {
        return match ($this) {
            self::ExecutionFlow => 500,
            default => 400,
        };
    }
}