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
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Server\Contracts\Request\SuccessResponseInterface;
use Charcoal\Http\Server\Exceptions\Request\ResponseBytesDispatchedException;
use Charcoal\Http\Server\HttpServer;

/**
 * Represents a response encapsulating an encoded buffer.
 * Implements the SuccessResponseInterface.
 * Provides methods to retrieve content length, cache status,
 * and to send the response content.
 */
final readonly class EncodedBufferResponse implements SuccessResponseInterface
{
    public function __construct(
        public int             $statusCode,
        public bool            $bypassedEncoder,
        public BufferImmutable $buffer,
        public bool            $isCacheable,
        public ContentType     $contentType = ContentType::Text,
        public ?Charset        $charset = null,
    )
    {
    }

    /**
     * @param Headers $headers
     * @return void
     */
    public function setHeaders(Headers $headers): void
    {
        $contentType = $this->contentType->value;
        if ($this->charset) {
            $charset = match ($this->contentType) {
                ContentType::Text,
                ContentType::Html,
                ContentType::Stylesheet,
                ContentType::Json,
                ContentType::Xml,
                ContentType::FormSubmit,
                ContentType::Javascript => true,
                default => false,
            };

            $contentType .= ($charset ? "; charset=" . match ($this->charset) {
                    Charset::ASCII => "US-ASCII",
                    default => $this->charset->value,
                } : "");
        }

        $headers->set("Content-Type", $contentType);
        $headers->set("Content-Length", (string)$this->buffer->length());
    }

    /**
     * @return bool
     */
    public function isCacheable(): bool
    {
        return $this->isCacheable;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return never
     * @throws ResponseBytesDispatchedException
     */
    public function send(): never
    {
        print($this->buffer->bytes());

        throw new ResponseBytesDispatchedException();
    }
}