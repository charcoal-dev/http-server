<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Logger;

use Charcoal\Http\Router\Contracts\HttpRouterLoggerInterface;

/**
 * Class LoggerCallback
 * @package Charcoal\Http\Router\Policy
 */
class LoggerCallback implements HttpRouterLoggerInterface
{
    public function __construct(
        public ?\Closure $onInvalidHeader = null,
        public ?\Closure $onInvalidPayload = null,
    )
    {
    }

    public function onInvalidHeader(): ?\Closure
    {
        return $this->onInvalidHeader;
    }

    public function onInvalidPayload(): ?\Closure
    {
        return $this->onInvalidPayload;
    }
}