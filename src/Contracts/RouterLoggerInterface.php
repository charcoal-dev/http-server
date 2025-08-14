<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts;

/**
 * Interface RouterLoggerInterface
 * @package Charcoal\Http\Router\Contracts
 */
interface RouterLoggerInterface
{
    public function onInvalidHeader(): ?\Closure;

    public function handlesInvalidHeader(): bool;

    public function onInvalidPayload(): ?\Closure;

    public function handlesInvalidPayload(): bool;
}