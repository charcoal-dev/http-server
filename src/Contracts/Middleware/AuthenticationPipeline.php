<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Server\Contracts\Controllers\Auth\AuthContextInterface;

/**
 * Represents a pipeline specifically handling authentication processes.
 * Extends the PipelineMiddlewareInterface to standardize middleware behavior.
 */
interface AuthenticationPipeline extends PipelineMiddlewareInterface
{
    public function __invoke(Headers $headers): AuthContextInterface;
}