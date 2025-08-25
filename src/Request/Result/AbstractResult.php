<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request\Result;

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
        Headers    $headers
    )
    {
        $this->headers = new HeadersImmutable($headers);
    }

    /**
     * @return bool
     * @api
     */
    final public function isCacheable(): bool
    {
        return $this instanceof SuccessResult ||
            $this instanceof RedirectResult;
    }
}