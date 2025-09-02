<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Pipelines;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Server\Contracts\Middleware\RequestBodyDecoderPipeline;
use Charcoal\Http\Server\Enums\ContentEncoding;
use Charcoal\Http\Server\Enums\RequestError;
use Charcoal\Http\Server\Enums\TransferEncoding;
use Charcoal\Http\Server\Exceptions\RequestGatewayException;
use Charcoal\Http\Server\Request\Controller\RequestFacade;

class RequestBodyDecoder implements RequestBodyDecoderPipeline
{

    final public function __invoke(RequestFacade $request)
    {
        $this->validateTransferEncoding($request->transferEncoding);
        $this->validateContentEncoding($request->contentType);

        if ($request->contentLength === 0) {
            if ($this->isBodyRequired($request->method)) {
                throw new \LengthException("Request body is required for method " . $request->method->name);
            }

            return null;
        }


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
     * @throws RequestGatewayException
     */
    protected function validateTransferEncoding(?TransferEncoding $transferEncoding): void
    {
        if ($transferEncoding && $transferEncoding !== TransferEncoding::Chunked) {
            throw new RequestGatewayException(RequestError::UnsupportedTransferEncoding, null);
        }
    }

    /**
     * @throws RequestGatewayException
     */
    protected function validateContentEncoding(?ContentEncoding $contentEncoding): void
    {
        if ($contentEncoding && $contentEncoding !== ContentEncoding::Identity) {
            throw new RequestGatewayException(RequestError::UnsupportedContentEncoding, null);
        }
    }

    /**
     * @param array $params
     * @return mixed
     */
    final public function execute(array $params): mixed
    {
        return $this->__invoke(...$params);
    }
}