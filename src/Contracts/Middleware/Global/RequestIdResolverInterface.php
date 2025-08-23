<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Global;

use Charcoal\Buffers\Frames\Bytes16;
use Charcoal\Http\Commons\Headers\HeadersImmutable;

/**
 * This interface is responsible for resolving a unique request ID for each incoming request.
 * It extends the GlobalMiddlewareInterface, ensuring its operations align with global middleware behavior.
 */
interface RequestIdResolverInterface extends GlobalMiddlewareInterface
{
    public function __invoke(HeadersImmutable $headers): Bytes16;
}