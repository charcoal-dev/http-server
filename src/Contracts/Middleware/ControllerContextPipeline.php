<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Server\Contracts\Controllers\Context\ControllerContextInterface;
use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;

/**
 * Represents a pipeline interface for processing a controller context.
 * Extends the behavior of the PipelineMiddlewareInterface.
 */
interface ControllerContextPipeline extends PipelineMiddlewareInterface
{
    /**
     * @param ControllerInterface $controller
     * @param Headers $headers
     * @return array<ControllerContextInterface>
     */
    public function __invoke(ControllerInterface $controller, Headers $headers): array;
}