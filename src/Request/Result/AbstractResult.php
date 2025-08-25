<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request\Result;

use Charcoal\Http\Commons\Body\PayloadImmutable;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Headers\HeadersImmutable;

/**
 * Represents an abstract result with immutable headers.
 */
abstract readonly class AbstractResult
{
    public HeadersImmutable $headers;
    public ?PayloadImmutable $payload;

    public function __construct(
        public int       $statusCode,
        Headers          $headers,
        ?WritablePayload $payload = null,
        public ?string   $body = null,
    )
    {
        $this->headers = new HeadersImmutable($headers);
        $this->payload = $payload ? new PayloadImmutable($payload) : null;
    }

    /**
     * @return bool
     * @api
     */
    public function isCacheable(): bool
    {
        return ($this instanceof SuccessResult && ($this->payload?->count() || strlen($this->body ?? ""))) ||
            $this instanceof RedirectResult;
    }
}