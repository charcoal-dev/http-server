<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

use Charcoal\Http\Server\Request\Logger\RequestLogPolicy;

/**
 * Enum representing the logging policies for handling HTTP requests and responses.
 * This enum defines several levels of logging granularity to determine what
 * information should be recorded during requests and responses.
 */
enum RequestLogPolicyEnum
{
    case Disabled;
    case Metadata;
    case HeadersOnly;
    case RequestOnly;
    case ResponseOnly;
    case Complete;

    /**
     * @return RequestLogPolicy
     */
    public function getPolicy(): RequestLogPolicy
    {
        return match ($this) {
            self::Disabled => new RequestLogPolicy(false, false, false, false, false),
            self::Metadata => new RequestLogPolicy(true, false, false, false, false),
            self::HeadersOnly => new RequestLogPolicy(true, true, false, true, false),
            self::RequestOnly => new RequestLogPolicy(true, true, true, false, false),
            self::ResponseOnly => new RequestLogPolicy(true, false, false, true, true),
            self::Complete => new RequestLogPolicy(true, true, true, true, true)
        };
    }
}