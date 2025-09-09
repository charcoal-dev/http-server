<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers\Hooks;

use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Server\Exceptions\Controllers\ResponseFinalizedException;
use Charcoal\Http\Server\Request\Controller\GatewayFacade;

/**
 * Interface representing a callback mechanism to be executed after the entry point of a controller.
 */
interface AfterEntrypointCallback extends ControllerInterface
{
    /**
     * @param GatewayFacade $request
     * @return void
     * @throws ResponseFinalizedException
     */
    public function afterEntrypointCallback(GatewayFacade $request): void;
}