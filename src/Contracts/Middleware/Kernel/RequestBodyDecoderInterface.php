<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Kernel;

use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareConstructableInterface;

/**
 * Defines a contract for a class responsible for decoding HTTP request bodies.
 * This interface combines the functionalities of KernelMiddlewareInterface and MiddlewareConstructableInterface.
 */
interface RequestBodyDecoderInterface extends KernelMiddlewareInterface, MiddlewareConstructableInterface
{
    public function __invoke(ContentType $contentType): ?UnsafePayload;
}