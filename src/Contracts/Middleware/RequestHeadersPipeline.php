<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Commons\Enums\HeaderKeyValidation;
use Charcoal\Http\Commons\Headers\HeadersImmutable;

/**
 * Represents a pipeline that processes HTTP request headers.
 * Implementations of this interface are expected to handle and manipulate
 * headers within HTTP requests as they pass through middleware layers.
 */
interface RequestHeadersPipeline extends PipelineMiddlewareInterface
{
    public function __invoke(
        HeadersImmutable    $headers,
        int                 $maxHeaders,
        int                 $maxHeaderLength,
        HeaderKeyValidation $keyValidation,
    ): HeadersImmutable;
}