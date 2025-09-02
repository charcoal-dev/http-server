<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

use Charcoal\Http\Server\Contracts\RequestErrorCodeInterface;

/**
 * Each case corresponds to a specific category of error that can occur during
 * processing of a request. The enum also provides a method to retrieve the
 * associated HTTP status code for the error type.
 */
enum RequestError implements RequestErrorCodeInterface
{
    case InternalError;

    /** @for=Peer */
    case BadPeerIp;
    case IncorrectHost;
    case TlsEnforced;

    /** @for=Url */
    case BadUrlLength;
    case BadUrlEncoding;

    /** @for=Headers */
    case BadHeaders;
    case HeadersCountCap;
    case BadHeaderName;
    case HeaderLength;
    case BadHeaderValue;

    /** @for=Routing */
    case EndpointNotFound;
    case ControllerResolveError;
    case MethodNotAllowed;

    /** @for=BodyHandler */
    case BadContentType;
    case BadContentLength;
    case ContentOverflow;
    case BadTransferEncoding;
    case UnsupportedTransferEncoding;
    case ContentHandlingConflict;
    case UnsupportedContentEncoding;

    /** @for=Cors */
    case BadOriginHeader;
    case CorsOriginNotAllowed;

    case RequestBodyDecodeError;

    /**
     * Determines and returns the appropriate HTTP status code
     * based on the current instance.
     */
    public function getStatusCode(): int
    {
        return match ($this) {
            self::BadUrlEncoding,
            self::BadHeaderName,
            self::BadHeaderValue,
            self::BadHeaders,
            self::BadOriginHeader,
            self::BadTransferEncoding,
            self::BadContentLength,
            self::BadPeerIp => 400,
            self::CorsOriginNotAllowed => 403,
            self::EndpointNotFound => 404,
            self::MethodNotAllowed => 405,
            self::ContentOverflow => 413,
            self::BadUrlLength => 414,
            self::UnsupportedTransferEncoding,
            self::UnsupportedContentEncoding,
            self::BadContentType => 415,
            self::IncorrectHost => 421,
            self::TlsEnforced => 426,
            self::HeadersCountCap,
            self::HeaderLength => 431,
            default => 500,
        };
    }
}