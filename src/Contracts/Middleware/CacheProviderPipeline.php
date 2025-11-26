<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Server\Contracts\Cache\CacheProviderInterface;

/**
 * Interface representing a pipeline for managing cache providers.
 * Extends the PipelineMiddlewareInterface to allow for middleware functionality
 * in handling cache providers.
 */
interface CacheProviderPipeline extends PipelineMiddlewareInterface
{
    public function __invoke(array $context = []): CacheProviderInterface;
}