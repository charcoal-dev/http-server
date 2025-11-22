<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Logger;

/**
 * Represents the configuration policy for logging request and response details.
 * This class provides control over which aspects of HTTP requests and responses
 * should be logged, such as headers, parameters, and the overall enabled state.
 */
final readonly class RequestLogPolicy
{
    public function __construct(
        public bool $enabled = false,
        public bool $requestHeaders = true,
        public bool $requestParams = false,
        public bool $responseHeaders = true,
        public bool $responseParams = true,
    )
    {
    }
}