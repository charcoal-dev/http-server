<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Controller;

use Charcoal\Base\Abstracts\Dataset\BatchEnvelope;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Server\Enums\ContentEncoding;
use Charcoal\Http\Server\Enums\TransferEncoding;
use Charcoal\Http\Server\Request\Bags\QueryParams;
use Charcoal\Http\Server\Request\Files\FileUpload;

/**
 * Encapsulates data associated with an HTTP request, providing a structured interface
 * for handling request headers, query parameters, and path parameters.
 */
final readonly class RequestFacade
{
    public array $pathParams;
    public UnsafePayload $payload;
    public ?Buffer $body;
    public ?FileUpload $upload;

    public function __construct(
        public string            $requestId,
        public HttpMethod        $method,
        public HeadersImmutable  $headers,
        public QueryParams       $queryParams,
        public ?ContentType      $contentType,
        public int               $contentLength,
        public ?TransferEncoding $transferEncoding,
        public ?ContentEncoding  $contentEncoding
    )
    {
    }

    /** @internal */
    public function setPathParams(array $pathParams): void
    {
        $this->pathParams = $pathParams;
    }

    /**
     * @throws WrappedException
     * @internal
     */
    public function initializeBody(FileUpload|Buffer|array $payload): void
    {
        $this->payload = new UnsafePayload(is_array($payload) ? new BatchEnvelope($payload) : null);
        $this->body = $payload instanceof Buffer ? ($payload)->readOnly() : null;
        $this->upload = $payload instanceof FileUpload ? $payload : null;
    }
}