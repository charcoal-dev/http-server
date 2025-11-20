<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Server\Contracts\Controllers\Auth\AuthContextInterface;
use Charcoal\Http\Server\Request\Controller\RequestFacade;

/**
 * Represents a pipeline specifically handling authentication processes.
 * Extends the PipelineMiddlewareInterface to standardize middleware behavior.
 */
interface AuthenticationPipeline extends PipelineMiddlewareInterface
{
    public function __invoke(RequestFacade $request): AuthContextInterface;
}