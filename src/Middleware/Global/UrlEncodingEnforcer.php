<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Global;

use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Router\Attributes\BindsTo;
use Charcoal\Http\Router\Contracts\Middleware\Global\UrlEncodingEnforcerInterface;
use Charcoal\Http\Router\Exceptions\Middleware\Global\UrlEncodingViolation;

/**
 * This class ensures that the given URL path complies with proper URL encoding
 * standards. If the path does not correctly roundup between encoding and decoding,
 * an exception is thrown to indicate a violation.
 */
#[BindsTo(UrlEncodingEnforcerInterface::class)]
final class UrlEncodingEnforcer implements UrlEncodingEnforcerInterface
{
    public function __invoke(UrlInfo $url): void
    {
        if (is_null($url->path) || $url->path === "/") {
            return;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $url->path)) {
            throw new UrlEncodingViolation("Control character in path");
        }

        if (preg_match("/%(?![0-9A-Fa-f]{2})/", $url->path)) {
            throw new UrlEncodingViolation("Bad percent-encoding in path");
        }

        if ($url->path !== rawurldecode(rawurlencode($url->path))) {
            throw new UrlEncodingViolation("Malformed URL path");
        }
    }
}