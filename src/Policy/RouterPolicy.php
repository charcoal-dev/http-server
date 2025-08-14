<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Policy;

use Charcoal\Http\Router\Contracts\HttpRouterLoggerInterface;

/**
 * Class ServerPolicy
 * @package Charcoal\Http\Router\Policy
 */
class RouterPolicy
{
    public function __construct(
        public readonly HeadersPolicy              $incomingHeaders,
        public readonly ?HttpRouterLoggerInterface $incomingLogger = null,
        public readonly PayloadPolicy              $incomingPayload,
        public readonly HeadersPolicy              $outgoingHeaders,
        public PayloadPolicy              $outgoingPayload,
    )
    {
    }
}