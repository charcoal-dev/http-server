<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result\Success;

use Charcoal\Buffers\BufferImmutable;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Server\Contracts\Request\SuccessResponseInterface;

/**
 * Represents a response encapsulating an encoded buffer.
 * Implements the SuccessResponseInterface.
 * Provides methods to retrieve content length, cache status,
 * and to send the response content.
 */
final readonly class EncodedBufferResponse implements SuccessResponseInterface
{
    public function __construct(
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

            $contentType = $charset ? "; charset=" . match ($this->charset) {
                    Charset::ASCII => "US-ASCII",
                    default => $this->charset->value,
                } : "";
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
     * @return void
     */
    public function send(): void
    {
        print($this->buffer->bytes());
    }
}