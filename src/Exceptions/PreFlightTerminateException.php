<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions;

/**
 * Exception thrown when a request is terminated due to a Cross-Origin Resource Sharing (CORS) policy.
 * This exception is used to indicate that the request should be terminated and,
 * no further processing should be performed.
 */
final class PreFlightTerminateException extends \Exception
{
    public function __construct(public readonly bool $success)
    {
        parent::__construct(self::class, 0, null);
    }
}