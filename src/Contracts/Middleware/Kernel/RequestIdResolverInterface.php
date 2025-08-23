<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Kernel;

use Charcoal\Buffers\Frames\Bytes16;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareConstructableInterface;

/**
 * This interface is responsible for resolving a unique request ID for each incoming request.
 * It extends the KernelMiddlewareInterface, ensuring its operations align with kernel` middleware behavior.
 */
interface RequestIdResolverInterface extends KernelMiddlewareInterface, MiddlewareConstructableInterface
{
    public function __invoke(HeadersImmutable $headers): Bytes16;
}