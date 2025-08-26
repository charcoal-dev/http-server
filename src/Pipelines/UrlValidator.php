<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Pipelines;

use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Server\Contracts\Middleware\UrlValidatorPipeline;
use Charcoal\Http\Server\Request\Result\RedirectUrl;

/**
 * The UrlValidatorPipeline class provides functionality for validating URLs
 * and applying constraints to determine if a redirect URL can be resolved.
 * @see UrlValidatorPipeline
 */
final readonly class UrlValidator implements UrlValidatorPipeline
{
    public function execute(array $params): ?RedirectUrl
    {
        return $this->__invoke(...$params);
    }

    /**
     * Handles the processing of a given URL and returns a redirect, if applicable,
     * based on the specified constraints and the path normalization rules.
     */
    public function __invoke(UrlInfo $url, int $maxUriBytes): ?RedirectUrl
    {
        if (strlen($url->complete) > $maxUriBytes) {
            throw new \LengthException("URL exceed maximum length: " . $maxUriBytes);
        }

        if (!$url->path || $url->path === "/") {
            return null;
        }

        if (preg_match("/[\x00-\x1F\x7F]/", $url->path)) {
            throw new \InvalidArgumentException("Control character in path");
        }

        if (str_contains($url->path, "%")) {
            throw new \InvalidArgumentException("Percent-encoding not allowed in path");
        }

        $normalized = preg_replace("/\/+/", "/", "/" . trim($url->path, "/"));
        if ($normalized !== rtrim($url->path, "/")) {
            return new RedirectUrl($url, 308, changePath: $normalized, absolute: false, queryStr: true);
        }

        // Only ASCII chars (with ._-) are allowed, Dot segments are not allowed
        if (!preg_match("/^(\/[A-Za-z0-9.\-_]*[A-Za-z0-9\-_])*\/?$/", $url->path)) {
            throw new \InvalidArgumentException("URL contains invalid characters");
        }

        return null;
    }
}