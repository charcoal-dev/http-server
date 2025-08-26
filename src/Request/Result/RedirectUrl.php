<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result;

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
        public ?string $changePath = null,
        public bool    $toggleScheme = false,
        public bool    $absolute = false,
        public bool    $queryStr = false,
    )
    {
    }

    /**
     * Constructs and returns a URL based on the provided parameters.
     * @api
     */
    public function getUrl(
        ?UrlInfo $previous = null,
        ?bool    $absolute = null,
        ?bool    $queryStr = null
    ): string
    {
        $previous ??= $this->previous;
        $absolute ??= $this->absolute;
        $queryStr ??= $this->queryStr;

        $redirectTo = $this->changePath ? "/" . ltrim($this->changePath, "/") : $this->previous->path;
        if ($queryStr) {
            $redirectTo .= $previous->query ? ("?" . $previous->query) : "";
            $redirectTo .= $previous->fragment ? ("#" . $previous->fragment) : "";
        }

        $scheme = $this->toggleScheme ? ($previous->scheme === "https" ? "http" : "https") : $previous->scheme;
        return $absolute && ($previous->scheme && $previous->host) ?
            (($scheme . "://") .
                ((str_contains($previous->host, ":") && $previous->host[0] !== "[") ?
                    ("[" . $previous->host . "]") : $previous->host) .
                ($previous->port ? (":" . $previous->port) : "") .
                $redirectTo) : $redirectTo;
    }
}