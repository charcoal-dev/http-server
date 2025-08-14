<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Policy;

use Charcoal\Http\Commons\Body\Payload;
use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Contracts\RouterLoggerInterface;

/**
 * Class ServerPolicy
 * @package Charcoal\Http\Router\Policy
 */
readonly class RouterPolicy
{
    public function __construct(
        public HeadersPolicy          $incomingHeaders,
        public PayloadPolicy          $incomingPayload,
        public HeadersPolicy          $outgoingHeaders,
        public PayloadPolicy          $outgoingPayload,
        public ?RouterLoggerInterface $incomingLogger = null,
        public bool                   $parsePayloadKeepBody = false,
        public string                 $parsePayloadUndefinedParam = "json"
    )
    {
    }

    /**
     * @return WritableHeaders
     * @throws \Charcoal\Base\Exceptions\WrappedException
     */
    public function createResponseHeaders(): WritableHeaders
    {
        return new WritableHeaders($this->outgoingHeaders, $this->outgoingHeaders->keyPolicy);
    }

    /**
     * @return Payload
     * @throws \Charcoal\Base\Exceptions\WrappedException
     */
    public function createResponsePayload(): Payload
    {
        return new Payload($this->outgoingPayload, $this->outgoingPayload->keyPolicy);
    }
}