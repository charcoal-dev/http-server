<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Server\Request\ServerRequest;

interface RequestBodyParserPipeline extends PipelineMiddlewareInterface
{
    public function __invoke(ContentType $contentType, int $contentLength, ServerRequest $request);
}