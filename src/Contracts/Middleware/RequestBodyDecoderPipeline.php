<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Server\Request\Controller\RequestFacade;

/**
 * Represents a pipeline responsible for decoding the body of incoming HTTP requests.
 */
interface RequestBodyDecoderPipeline extends PipelineMiddlewareInterface
{
    /**
     * Parse incoming request body
     */
    public function __invoke(RequestFacade $request);
}