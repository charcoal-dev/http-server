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
    case BadPeerIp;
    case BadHostname;
    case IncorrectHost;
    case TlsEnforcedRedirect;
    case RequestIdError;
    case BadUrlEncoding;
    case BadUrlLength;
    case UrlNormalizedRedirect;

    /**
     * Determines and returns the appropriate HTTP status code
     * based on the current instance.
     */
    public function getStatusCode(): int
    {
        return match ($this) {
            self::RequestIdError,
            self::BadUrlEncoding,
            self::BadUrlLength => 414,
            default => 500
        };
    }
}