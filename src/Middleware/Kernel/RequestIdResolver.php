<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Kernel;

use Charcoal\Buffers\Frames\Bytes16;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Router\Attributes\BindsTo;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\RequestIdResolverInterface;

/**
 * This class implements a method to extract a specific request ID
 * from HTTP headers. If a valid ID is found and matches the
 * expected format, it is returned as a Bytes16 instance. Otherwise,
 * a new random ID is generated and returned.
 */
#[BindsTo(RequestIdResolverInterface::class)]
final class RequestIdResolver implements RequestIdResolverInterface
{
    /**
     * Constructor as required by MiddlewareConstructableInterface
     */
    public function __construct()
    {
    }

    /**
     * Resolves and returns the request ID from the HTTP headers.
     */
    public function __invoke(HeadersImmutable $headers): Bytes16
    {
        $requestId = trim((string)$headers->get("X-Request-Id"));
        if (!$requestId) {
            return Bytes16::fromRandomBytes();
        }

        if (strlen($requestId) === 36 && str_contains($requestId, "-")) {
            $requestId = str_replace("-", "", $requestId);
        }

        if (strlen($requestId) === 32 && ctype_xdigit($requestId) && $requestId !== str_repeat("0", 32)) {
            return Bytes16::fromBase16($requestId);
        }

        return Bytes16::fromRandomBytes();
    }
}