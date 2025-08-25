<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Controllers;

use Charcoal\Http\Router\Request\ControllerContext;

/**
 * Represents a controller interface that ensures the implementation of an invokable method.
 * Classes implementing this interface are intended to be callable as single-action controllers.
 */
interface InvokableControllerInterface extends ControllerInterface
{
    public function __invoke(ControllerContext $context): void;
}