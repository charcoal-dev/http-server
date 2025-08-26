<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

/**
 * Represents a middleware component to be used within a processing pipeline.
 * The middleware performs operations on the provided parameters and
 * optionally manipulates or forwards them further in the pipeline.
 */
interface PipelineMiddlewareInterface
{
    public function invoke(array $params): mixed;
}