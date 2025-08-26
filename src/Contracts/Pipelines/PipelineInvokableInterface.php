<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Pipelines;

/**
 * Extends the PipelineInterface to define components that can
 * be invoked as part of a processing pipeline.
 */
interface PipelineInvokableInterface extends PipelineInterface
{
}