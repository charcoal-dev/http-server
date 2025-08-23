<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Kernel;

use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Router\Attributes\BindsTo;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\UrlEncodingEnforcerInterface;

/**
 * This class ensures that the given URL path complies with proper URL encoding
 * standards. If the path does not correctly roundup between encoding and decoding,
 * an exception is thrown to indicate a violation.
 */
#[BindsTo(UrlEncodingEnforcerInterface::class)]
final class UrlEncodingEnforcer implements UrlEncodingEnforcerInterface
{
    /**
     * Constructor as required by MiddlewareConstructableInterface
     */
    public function __construct()
    {
    }

    /**
     * Ensures that the given URL path complies with proper URL encoding standards.
     */
    public function __invoke(UrlInfo $url): void
    {
        if (is_null($url->path) || $url->path === "/") {
            return;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $url->path)) {
            throw new \InvalidArgumentException("URL path contains invalid characters");
        }

        if (preg_match("/%(?![0-9A-Fa-f]{2})/", $url->path)) {
            throw new \InvalidArgumentException("Bad percent-encoding in path");
        }

        if ($url->path !== rawurldecode(rawurlencode($url->path))) {
            throw new \InvalidArgumentException("Malformed URL path");
        }
    }
}