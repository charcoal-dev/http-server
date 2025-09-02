<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Pipelines;

use Charcoal\Http\Server\Contracts\Middleware\ControllerContextFacadePipeline;
use Charcoal\Http\Server\Request\Controller\RequestFacade;
use Charcoal\Http\Server\Request\RequestGateway;

/**
 * Represents a resolver that facilitates the handling of controller context facades
 * by executing the pipeline and invoking the necessary operations to produce a RequestFacade.
 */
final readonly class ControllerContextFacadeResolver implements ControllerContextFacadePipeline
{
    public function execute(array $params): RequestFacade
    {
        return $this->__invoke(...$params);
    }

    public function __invoke(RequestGateway $request): RequestFacade
    {
        return new RequestFacade($request);
    }
}