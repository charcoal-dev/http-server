<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Kernel;

use Charcoal\Http\Router\Request\ControllerContext;
use Charcoal\Http\Router\Request\RequestContext;

/**
 * An interface that resolves and provides the context for the controller during the handling of a request.
 * It extends the `KernelMiddlewareInterface` to integrate with the application's middleware handling process.
 */
interface ControllerContextResolverInterface extends KernelMiddlewareInterface
{
    public function __invoke(RequestContext $requestContext): ControllerContext;
}