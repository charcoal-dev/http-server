<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts;

/**
 * Interface HttpRouterLoggerInterface
 * @package Charcoal\Http\Router\Contracts
 */
interface HttpRouterLoggerInterface
{
    public function onInvalidHeader(): ?\Closure;

    public function onInvalidPayload(): ?\Closure;
}