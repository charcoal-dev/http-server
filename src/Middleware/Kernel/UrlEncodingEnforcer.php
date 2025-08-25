<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Kernel;

use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Router\Attributes\Middleware\BindsTo;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\UrlEncodingEnforcerInterface;
use Charcoal\Http\Router\Request\Result\RedirectUrl;

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
    public function __invoke(UrlInfo $url): ?RedirectUrl
    {
        if (is_null($url->path) || $url->path === "/") {
            return null;
        }

        if (strlen($url->path) > 256) {
            throw new \InvalidArgumentException("URL path is too long; Maximum 256 bytes allowed", 414);
        }

        if (preg_match("/[\x00-\x1F\x7F]/", $url->path)) {
            throw new \InvalidArgumentException("Control character in path");
        }

        if (str_contains($url->path, "%")) {
            throw new \InvalidArgumentException("Percent-encoding not allowed in path");
        }

        $normalized = preg_replace("/\/+/", "/", "/" . trim($url->path, "/"));
        if ($normalized !== rtrim($url->path, "/")) {
            return new RedirectUrl($url, 308, $normalized, absolute: false, queryStr: true);
        }

        // Only ASCII chars (with ._-) are allowed, Dot segments are not allowed
        if (!preg_match("/^(\/[A-Za-z0-9.\-_]*[A-Za-z0-9\-_])*\/?$/", $url->path)) {
            throw new \InvalidArgumentException("URL contains invalid characters");
        }

        return null;
    }
}