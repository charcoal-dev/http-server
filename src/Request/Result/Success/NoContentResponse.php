<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result\Success;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Server\Contracts\Request\SuccessResponseInterface;

/**
 * No Content Response
 */
final readonly class NoContentResponse implements SuccessResponseInterface
{
    public function send(): void
    {
    }

    public function setHeaders(Headers $headers): void
    {
    }

    public function isCacheable(): bool
    {
        return false;
    }
}