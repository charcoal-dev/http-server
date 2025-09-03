<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Pipelines;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Support\HttpHelper;
use Charcoal\Http\Server\Contracts\Middleware\RequestBodyDecoderPipeline;
use Charcoal\Http\Server\Enums\ContentEncoding;
use Charcoal\Http\Server\Enums\TransferEncoding;
use Charcoal\Http\Server\Internal\Constants;
use Charcoal\Http\Server\Request\Controller\RequestFacade;
use Charcoal\Http\Server\Request\Files\FileUpload;
use Charcoal\Http\Server\Support\StreamReader;

/**
 * RequestBodyDecoder is responsible for decoding the request body based on the content type and other constraints.
 * It supports various content types and validates the body based on the specified rules.
 * This decoder can also handle file uploads and ensures proper handling of request size limits.
 */
class RequestBodyDecoder implements RequestBodyDecoderPipeline
{
    /**
     * @param RequestFacade $request
     * @param false|array $allowFileUpload
     * @param int $maxBodyBytes
     * @param int $maxParams
     * @param int $maxParamLength
     * @param int $maxDepth
     * @param Buffer|string|null $body
     * @return Buffer|FileUpload|array|null
     */
    final public function __invoke(
        RequestFacade $request,
        false|array   $allowFileUpload,
        int           $maxBodyBytes,
        int           $maxParams,
        int           $maxParamLength,
        int           $maxDepth,
        Buffer|string $body = null,
    ): Buffer|FileUpload|array|null
    {
        $this->validateTransferEncoding($request->transferEncoding);
        $this->validateContentEncoding($request->contentType);
        $contentLength = $request->contentLength;
        if ($request->transferEncoding && $contentLength > 0) {
            throw new \OutOfBoundsException("Content-Length and Transfer-Encoding are mutually exclusive");
        }

        if ($maxBodyBytes < 0) {
            throw new \RuntimeException("Bad constraint value maxBodyBytes: " . $maxBodyBytes);
        }

        if ($maxBodyBytes > $contentLength) {
            throw new \OverflowException("Content length of %d exceeds maximum allowed %d bytes",
                $contentLength, $contentLength);
        }

        if ($request->contentLength === 0) {
            if ($this->isBodyRequired($request->method)) {
                throw new \LengthException("Request body is required for method " . $request->method->name);
            }

            return null;
        }

        // File Uploads
        if ($request->contentType === ContentType::OctetStream) {
            if (!is_string($body) || !$body) {
                throw new \InvalidArgumentException("Invalid path to body stream");
            }

            if (!$allowFileUpload) {
                throw new \DomainException("File uploads are not allowed", 2);
            }

            return $this->handleFileUpload($body, $allowFileUpload["size"], $request->contentLength);
        }

        // JSON, Form Submit, Text...
        if (!$this->supportsContentType($request->contentType)) {
            throw new \DomainException("Content type " . $request->contentType->value . " is not supported", 1);
        }

        return $this->handlePayload($request->contentType, $contentLength, $body, $maxDepth, $maxParamLength);
    }

    /**
     * @param string $stream
     * @param int $allowedFileSize
     * @param int $contentLength
     * @return FileUpload
     */
    private function handleFileUpload(string $stream, int $allowedFileSize, int $contentLength): FileUpload
    {
        if ($allowedFileSize <= 0) {
            throw new \InvalidArgumentException("Allowed file size must be a positive integer");
        }

        if ($contentLength > 0 && $contentLength > $allowedFileSize) {
            throw new \OverflowException("Content length of %d exceeds maximum allowed %d bytes",
                $contentLength, $allowedFileSize);
        }

        $limit = ($contentLength > 0)
            ? min($contentLength, Constants::HARD_LIMIT_REQ_UPLOAD)
            : Constants::HARD_LIMIT_REQ_UPLOAD;

        $tmp = StreamReader::readStreamToTempFiles($stream, $limit);
        return new FileUpload($tmp["tmpPath"], $tmp["size"]);
    }

    /**
     * @param ContentType $contentType
     * @param int $contentLength
     * @param Buffer|string $body
     * @param int $maxDepth
     * @param int $maxParamLength
     * @return Buffer|array
     */
    private function handlePayload(
        ContentType   $contentType,
        int           $contentLength,
        Buffer|string $body,
        int           $maxDepth,
        int           $maxParamLength
    ): Buffer|array
    {
        $limit = min($contentLength, Constants::HARD_LIMIT_MEMORY_REQ_BODY);
        $body = match (true) {
            $body instanceof Buffer => $this->openBufferForBody($body, $limit),
            is_string($body) => StreamReader::readStreamToMemory($body, $limit),
            default => throw new \RuntimeException("Invalid body type: " . gettype($body)),
        };

        if (!mb_check_encoding($body, "UTF-8")) {
            throw new \DomainException("Invalid UTF-8 encoding in request body", 3);
        }

        if ($contentType === ContentType::Text) {
            return new Buffer($body);
        }

        return $this->decodePayloadFrom($contentType, $body, $maxDepth, $maxParamLength);
    }

    /**
     * Extend this method to add support for other content types.
     */
    protected function decodePayloadFrom(
        ContentType $contentType,
        string      $body,
        int         $maxDepth,
        int         $maxParamLength
    ): array
    {
        if ($contentType === ContentType::Json) {
            return json_decode($body, true, $maxDepth, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        }

        if ($contentType === ContentType::FormSubmit) {
            return HttpHelper::parseQueryString(
                $body,
                plusAsSpace: true,
                utf8Encoding: true,
                maxKeyLength: 64,
                maxValueLength: $maxParamLength,
                flatten: true
            );
        }

        throw new \DomainException("Unsupported content type: " . $contentType->value, 1);
    }

    /**
     * @param Buffer $body
     * @param int $limit
     * @return string
     */
    private function openBufferForBody(Buffer $body, int $limit): string
    {
        if ($body->len() !== $limit) {
            throw new \UnderflowException("Request body is incomplete; Expected " .
                $limit . "bytes, got " . $body->len());
        }

        return $body->raw();
    }

    /**
     * @param ContentType $contentType
     * @return bool
     */
    protected function supportsContentType(ContentType $contentType): bool
    {
        return match ($contentType) {
            ContentType::Text,
            ContentType::Json,
            ContentType::FormSubmit => true,
            default => false
        };
    }

    /**
     * @param HttpMethod $method
     * @return bool
     */
    protected function isBodyRequired(HttpMethod $method): bool
    {
        return match ($method) {
            HttpMethod::GET, HttpMethod::HEAD, HttpMethod::OPTIONS => false,
            default => true
        };
    }

    /**
     * @param TransferEncoding|null $transferEncoding
     * @return void
     */
    protected function validateTransferEncoding(?TransferEncoding $transferEncoding): void
    {
        if ($transferEncoding && $transferEncoding !== TransferEncoding::Chunked) {
            throw new \DomainException("Transfer encoding " . $transferEncoding->value . " is not supported", 5);
        }
    }

    /**
     * @param ContentEncoding|null $contentEncoding
     * @return void
     */
    protected function validateContentEncoding(?ContentEncoding $contentEncoding): void
    {
        if ($contentEncoding && $contentEncoding !== ContentEncoding::Identity) {
            throw new \DomainException("Content encoding " . $contentEncoding->value . " is not supported", 4);
        }
    }

    /**
     * @param array $params
     * @return Buffer|FileUpload|array|null
     */
    final public function execute(array $params): Buffer|FileUpload|array|null
    {
        return $this->__invoke(...$params);
    }
}