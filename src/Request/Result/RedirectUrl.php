<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result;

use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Server\Config\VirtualHost;

/**
 * Represents URL redirection details, including information about the original URL,
 * redirection path, and additional configurations such as query strings.
 */
final readonly class RedirectUrl
{
    public function __construct(
        public UrlInfo      $previous,
        public int          $statusCode,
        public ?string      $changePath = null,
        public ?VirtualHost $changeHost = null,
        public ?bool        $tlsScheme = null,
        public bool         $absolute = false,
        public bool         $queryStr = false,
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
        $tlsScheme ??= $this->tlsScheme;

        $redirectTo = $this->changePath ? "/" . ltrim($this->changePath, "/") : $this->previous->path;
        if ($queryStr) {
            $redirectTo .= $previous->query ? ("?" . $previous->query) : "";
            $redirectTo .= $previous->fragment ? ("#" . $previous->fragment) : "";
        }

        $scheme = $tlsScheme ? "https" : $this->previous->scheme;
        $hostname = $this->changeHost ? $this->changeHost->hostname : $this->previous->host;
        $port = $this->changeHost ? ($this->changeHost->ports[0] ?? null) : null;
        if ($port === 80 || $port === 443) {
            $port = null;
        }

        return $absolute && ($scheme && $hostname) ?
            (($scheme . "://") .
                ((str_contains($hostname, ":") && $hostname[0] !== "[") ? ("[" . $hostname . "]") : $hostname) .
                ($previous->port ? (":" . $previous->port) : "") .
                $redirectTo) : $redirectTo;
    }
}