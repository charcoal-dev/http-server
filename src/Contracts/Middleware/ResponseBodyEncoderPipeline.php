<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Http\Commons\Body\PayloadImmutable;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Server\Request\Result\Response\EncodedResponseBody;

/**
 * The implementation of this interface handles the encoding of a payload (response body)
 * into a specific format based on the provided content type and character set.
 */
interface ResponseBodyEncoderPipeline extends PipelineMiddlewareInterface
{
    public function __invoke(
        ContentType      $contentType,
        Charset          $charset,
        PayloadImmutable $response
    ): EncodedResponseBody;
}