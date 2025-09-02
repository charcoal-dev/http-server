<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Server\Request\Controller\GatewayFacade;
use Charcoal\Http\Server\Request\RequestGateway;

/**
 * Declare a facade/context given to controllers for execution.
 * Default RequestFace exposes the recommended amount of context to controllers.
 */
interface ControllerGatewayFacadePipeline extends PipelineMiddlewareInterface
{
    public function __invoke(RequestGateway $gateway): GatewayFacade;
}