<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Internal;

/**
 * Exception thrown when a request is terminated due to a Cross-Origin Resource Sharing (CORS) policy.
 * This exception is used to indicate that the request should be terminated and,
 * no further processing should be performed.
 * @internal
 */
final class PreFlightTerminateException extends \Exception
{
}