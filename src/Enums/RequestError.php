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
    case ForwardingIpBlocked;
    case IncorrectHost;
    case TlsEnforced;

    /** @for=Url */
    case BadUrlLength;
    case BadUrlEncoding;
    case QueryParamDecode;

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
    case BodyDecodeError;
    case BadContentType;
    case BadContentLength;
    case ContentOverflow;
    case BadTransferEncoding;
    case UnsupportedTransferEncoding;
    case ContentHandlingConflict;
    case UnsupportedContentEncoding;
    case BodyRequired;
    case MalformedBody;
    case FileUploadDisabled;
    case BadBodyCharset;
    case ParamsOverflow;
    case ParamValidation;
    case BodyDisabled;

    /** @for=ResponseHandler */
    case CacheProviderError;
    case CacheLookupError;
    case CacheResponseStore;
    case ResponseEncodeError;

    /** @for=Cors */
    case BadOriginHeader;
    case CorsOriginNotAllowed;

    /** @for=Logger */
    case LoggerInitError;
    case LogInitError;
    case LogAuthDataError;
    case LogRequestParamsError;

    /** @for=Authentication */
    case AuthenticationFailed;
    case Unauthorized;

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
            self::ParamValidation,
            self::ForwardingIpBlocked,
            self::BodyDecodeError,
            self::ContentHandlingConflict,
            self::MalformedBody,
            self::BadPeerIp => 400,
            self::Unauthorized => 401,
            self::CorsOriginNotAllowed => 403,
            self::EndpointNotFound => 404,
            self::MethodNotAllowed => 405,
            self::BodyRequired => 411,
            self::ParamsOverflow,
            self::BodyDisabled,
            self::ContentOverflow => 413,
            self::BadUrlLength => 414,
            self::FileUploadDisabled,
            self::UnsupportedTransferEncoding,
            self::UnsupportedContentEncoding,
            self::BadBodyCharset,
            self::BadContentType => 415,
            self::IncorrectHost => 421,
            self::TlsEnforced => 426,
            self::HeadersCountCap,
            self::HeaderLength => 431,
            default => 500
        };
    }
}