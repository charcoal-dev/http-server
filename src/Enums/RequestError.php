<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Enums;

/**
 * Each case corresponds to a specific category of error that can occur during
 * processing of a request. The enum also provides a method to retrieve the
 * associated HTTP status code for the error type.
 */
enum RequestError
{
    case KernelError;
    case RequestIdError;
    case BadUrlEncoding;
    case BadUrlLength;
    case ClientIpError;

    /**
     * Determines and returns the appropriate HTTP status code
     * based on the current instance.
     */
    public function getStatusCode(): int
    {
        return match ($this) {
            self::RequestIdError,
            self::BadUrlEncoding,
            self::ClientIpError => 400,
            self::BadUrlLength => 414,
            default => 500
        };
    }
}