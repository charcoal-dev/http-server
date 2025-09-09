<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Server\Request\Controller\RequestFacade;
use Charcoal\Http\Server\Request\Files\FileUpload;

/**
 * Represents a pipeline responsible for decoding the body of incoming HTTP requests.
 */
interface RequestBodyDecoderPipeline extends PipelineMiddlewareInterface
{
    /**
     * @param RequestFacade $request
     * @param bool $bodyDisabled
     * @param array{size: int}|false $allowFileUpload
     * @param int $maxBodyBytes
     * @param int $maxParams
     * @param int $maxParamLength
     * @param int $maxDepth
     * @param Buffer|string|null $body
     * @return Buffer|FileUpload|array|null
     */
    public function __invoke(
        RequestFacade $request,
        bool          $bodyDisabled,
        false|array   $allowFileUpload,
        int           $maxBodyBytes,
        int           $maxParams,
        int           $maxParamLength,
        int           $maxDepth,
        Buffer|string $body = null,
    ): Buffer|FileUpload|array|null;
}