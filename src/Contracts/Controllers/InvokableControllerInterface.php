<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers;

use Charcoal\Http\Server\Request\Controller\RequestFacade;

/**
 * Represents a controller interface that ensures the implementation of an invokable method.
 * Classes implementing this interface are intended to be callable as single-action controllers.
 */
interface InvokableControllerInterface extends ControllerInterface
{
    public function __invoke(RequestFacade $request): void;
}