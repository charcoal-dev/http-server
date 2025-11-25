<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers\Hooks;

use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Server\Request\Controller\GatewayFacade;

/**
 * Describes a contract for classes that need to execute logic
 * before the entry point of a request is handled.
 */
interface BeforeEntrypointCallback extends ControllerInterface
{
    /**
     * @param GatewayFacade $gateway
     * @return void
     */
    public function beforeEntrypointCallback(GatewayFacade $gateway): void;
}