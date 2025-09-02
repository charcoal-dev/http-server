<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Controller;

use Charcoal\Base\Abstracts\Dataset\BatchEnvelope;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Server\Enums\TransferEncoding;
use Charcoal\Http\Server\Request\Bags\QueryParams;

/**
 * Encapsulates data associated with an HTTP request, providing a structured interface
 * for handling request headers, query parameters, and path parameters.
 */
final readonly class RequestFacade
{
    public UnsafePayload $payload;
    public array $pathParams;

    public function __construct(
        public string            $requestId,
        public HttpMethod        $method,
        public HeadersImmutable  $headers,
        public QueryParams       $queryParams,
        public ?ContentType      $contentType,
        public int               $contentLength,
        public ?TransferEncoding $transferEncoding,
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
    public function initializePayload(BatchEnvelope $payload): void
    {
        $this->payload = new UnsafePayload($payload);
    }
}