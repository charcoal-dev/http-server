<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Contracts\Request\SuccessResponseInterface;
use Charcoal\Http\Server\HttpServer;

/**
 * Represents a cached result containing the HTTP headers, the response, and the timestamp of caching.
 * The response provided during construction must be cacheable; otherwise, a BadMethodCallException is thrown.
 */
final readonly class CachedResult extends AbstractResult
{
    public function __construct(
        Headers                         $headers,
        public SuccessResponseInterface $response,
        public ?string                  $integrityTag,
        public \DateTimeImmutable       $timestamp,
        public ?CacheControlDirectives  $cacheControl
    )
    {
        $response->setHeaders($headers);
        if ($this->cacheControl && $this->response->isCacheable()) {
            $headers->set("Cache-Control", implode(", ", $this->cacheControl->directives));
        }

        if (HttpServer::$exposeCachedOnHeader) {
            $headers->set(HttpServer::$exposeCachedOnHeader,
                $this->timestamp->format(HttpServer::$exposeCachedOnFormat));
        }

        parent::__construct($this->response->getStatusCode(), $headers);
    }
}