<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Server\Request\Logger\RequestLoggerConstructor;

/**
 * Interface RequestLoggerPipeline
 * Represents a middleware pipeline specifically designed for logging requests.
 * Extends the PipelineMiddlewareInterface.
 */
interface RequestLoggerPipeline extends PipelineMiddlewareInterface
{
    public function __invoke(): ?RequestLoggerConstructor;
}