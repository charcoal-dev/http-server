<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Controllers;

use Charcoal\Buffers\BufferImmutable;
use Charcoal\Contracts\Buffers\ReadableBufferInterface;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Server\Contracts\Request\SuccessResponseInterface;
use Charcoal\Http\Server\Request\Result\Success\EncodedBufferResponse;
use Charcoal\Http\Server\Request\Result\Success\NoContentResponse;

/**
 * Interrupt during controller execution to bypass the encoding process.
 */
final class BypassEncodingException extends ResponseFinalizedException
{
    public function __construct(
        public readonly ?ReadableBufferInterface $responseBody,
        public readonly bool                     $isCacheable = false,
        public readonly ContentType              $contentType = ContentType::Text,
        int                                      $statusCode = 200,
        public readonly ?Charset                 $charset = null,
    )
    {
        parent::__construct($statusCode);
    }

    public function getResponseObject(): SuccessResponseInterface
    {
        if ($this->responseBody === null || $this->responseBody->length() === 0) {
            return new NoContentResponse();
        }

        return new EncodedBufferResponse(
            statusCode: $this->statusCode,
            bypassedEncoder: true,
            buffer: new BufferImmutable($this->responseBody->bytes()),
            isCacheable: $this->isCacheable,
            contentType: $this->contentType,
            charset: $this->charset,
        );
    }
}