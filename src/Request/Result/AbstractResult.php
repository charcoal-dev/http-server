<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Headers\HeadersImmutable;

/**
 * Represents an abstract result with immutable headers.
 */
abstract readonly class AbstractResult
{
    public HeadersImmutable $headers;

    public function __construct(
        public int $statusCode,
        Headers    $headers,
    )
    {
        $this->headers = new HeadersImmutable($headers);
    }

    /**
     * @return bool
     * @api
     */
    public function isCacheable(): bool
    {
        return false;
    }
}