<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request\Result;

use Charcoal\Http\Commons\Url\UrlInfo;

/**
 * Represents URL redirection details, including information about the original URL,
 * redirection path, and additional configurations such as query strings.
 */
final readonly class RedirectUrl
{
    public function __construct(
        public UrlInfo $previous,
        public int     $statusCode,
        public string  $path,
        public bool    $absolute,
        public bool    $queryStr = false,
    )
    {
    }

    /**
     * Constructs and returns a URL string based on the provided path, query, fragment,
     * scheme, host, and port details. It supports both absolute and relative URLs.
     * @return string The constructed URL based on the current object's properties.
     */
    public function getUrl(?UrlInfo $previous): string
    {
        $previous ??= $this->previous;
        $redirectTo = "/" . ltrim($this->path, "/");
        if ($this->queryStr) {
            $redirectTo .= $previous->query ? ("?" . $previous->query) : "";
            $redirectTo .= $previous->fragment ? ("#" . $previous->fragment) : "";
        }

        return $this->absolute && ($previous->scheme && $previous->host) ?
            (($previous->scheme . "://") .
                ((str_contains($previous->host, ":") && $previous->host[0] !== "[") ?
                    ("[" . $previous->host . "]") : $previous->host) .
                ($previous->port ? (":" . $previous->port) : "") .
                $redirectTo) : $redirectTo;
    }
}