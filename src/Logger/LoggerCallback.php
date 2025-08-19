<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Logger;

use Charcoal\Http\Router\Contracts\RouterLoggerInterface;

/**
 * Class LoggerCallback
 * @package Charcoal\Http\Router\Policy
 * @property (\Closure(string|int, \Throwable): void)|null $onInvalidHeader
 * @property (\Closure(string|int, \Throwable): void)|null $onInvalidPayload
 */
class LoggerCallback implements RouterLoggerInterface
{
    public function __construct(
        public ?\Closure $onInvalidHeader = null,
        public ?\Closure $onInvalidPayload = null,
    )
    {
    }

    /**
     * @return (\Closure(string|int, \Throwable): void)|null
     */
    public function onInvalidHeader(): ?\Closure
    {
        return $this->onInvalidHeader;
    }

    /**
     * @return (\Closure(string|int, \Throwable): void)|null
     */
    public function onInvalidPayload(): ?\Closure
    {
        return $this->onInvalidPayload;
    }
}