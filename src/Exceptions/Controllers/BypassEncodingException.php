<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Controllers;

use Charcoal\Contracts\Buffers\ReadableBufferInterface;

/**
 * Exception thrown when there is an issue bypassing encoding for a provided response body.
 */
final class BypassEncodingException extends \Exception
{
    public function __construct(
        public readonly ReadableBufferInterface $responseBody,
        public readonly int                     $statusCode = 200
    )
    {
        parent::__construct();
    }
}