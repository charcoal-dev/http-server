<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result\Response;

use Charcoal\Buffers\BufferImmutable;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Http\Commons\Enums\ContentType;

/**
 * EncodedResponseBody is a final, immutable class that represents a response body
 * with specific encoding details, including content type, character set,
 * and the associated immutable buffer containing the response data.
 */
final readonly class EncodedResponseBody
{
    public function __construct(
        public ContentType     $contentType,
        public Charset         $charset,
        public BufferImmutable $buffer,
    )
    {
    }
}