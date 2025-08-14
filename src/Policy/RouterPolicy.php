<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Policy;

use Charcoal\Http\Router\Contracts\RouterLoggerInterface;

/**
 * Class ServerPolicy
 * @package Charcoal\Http\Router\Policy
 */
readonly class RouterPolicy
{
    public function __construct(
        public HeadersPolicy          $incomingHeaders,
        public ?RouterLoggerInterface $incomingLogger = null,
        public PayloadPolicy          $incomingPayload,
        public HeadersPolicy          $outgoingHeaders,
        public PayloadPolicy          $outgoingPayload,
        public bool                   $parsePayloadKeepBody = false,
        public string                 $parseScalarPayloadParam = "_json"
    )
    {
    }
}