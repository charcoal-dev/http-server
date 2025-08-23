<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

use Charcoal\Http\Router\Router;

final class RequestContext
{
    /** @var string Binary 16-byte UUID/hex */
    public readonly string $requestId;

    public function __construct(
        protected readonly Router        $router,
        protected readonly ServerRequest $request,
    )
    {
        // 1. Resolve a unique request ID
    }
}