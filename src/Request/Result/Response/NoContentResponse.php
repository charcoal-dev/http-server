<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result\Response;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Server\Contracts\Request\SuccessResponseInterface;
use Charcoal\Http\Server\Exceptions\Request\ResponseBytesDispatchedException;

/**
 * No Content Response
 */
final readonly class NoContentResponse implements SuccessResponseInterface
{
    public function __construct(public int $statusCode = 204)
    {
    }

    public function setHeaders(Headers $headers): void
    {
    }

    public function isCacheable(): bool
    {
        return false;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return never
     * @throws ResponseBytesDispatchedException
     */
    public function send(): never
    {
        throw new ResponseBytesDispatchedException();
    }
}