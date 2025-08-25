<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Kernel;

use Charcoal\Http\Commons\Body\PayloadImmutable;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareConstructableInterface;

/**
 * Interface ResponseBodyEncoderInterface
 * @package Charcoal\Http\Router\Contracts\Middleware\Kernel
 */
interface ResponseBodyEncoderInterface extends KernelMiddlewareInterface, MiddlewareConstructableInterface
{
    public function __invoke(ContentType $contentType, PayloadImmutable $payload): string;
}