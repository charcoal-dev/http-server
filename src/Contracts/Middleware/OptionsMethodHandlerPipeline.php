<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Support\CorsPolicy;
use Charcoal\Http\Server\Exceptions\PreFlightTerminateException;

/**
 * Interface representing a pipeline for handling the OPTIONS method within a CORS middleware process.
 * This interface extends the PipelineMiddlewareInterface and provides a mechanism for processing
 * requests to HTTP method OPTIONS with a specified origin and CORS policy.
 */
interface OptionsMethodHandlerPipeline extends PipelineMiddlewareInterface
{
    /**
     * @see PreFlightTerminateException
     */
    public function __invoke(string $origin, CorsPolicy $corsPolicy, Headers $response): void;
}