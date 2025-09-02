<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Pipelines;

use Charcoal\Http\Server\Contracts\Middleware\ControllerGatewayFacadePipeline;
use Charcoal\Http\Server\Request\Controller\GatewayFacade;
use Charcoal\Http\Server\Request\RequestGateway;

/**
 * Represents a resolver that facilitates the handling of controller context facades
 * by executing the pipeline and invoking the necessary operations to produce a RequestFacade.
 */
final readonly class ControllerGatewayFacadeResolver implements ControllerGatewayFacadePipeline
{
    public function execute(array $params): GatewayFacade
    {
        return $this->__invoke(...$params);
    }

    public function __invoke(RequestGateway $request): GatewayFacade
    {
        return new GatewayFacade($request);
    }
}