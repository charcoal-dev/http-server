<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Pipelines;

use Charcoal\Http\Server\Contracts\Middleware\RequestLoggerPipeline;

/**
 * Represents a read-only class that does not log any request data.
 * Implements the RequestLoggerPipeline interface without performing any actions.
 */
final readonly class NoRequestLog implements RequestLoggerPipeline
{
    public function execute(array $params): null
    {
        return null;
    }

    public function __invoke(): null
    {
        return null;
    }
}