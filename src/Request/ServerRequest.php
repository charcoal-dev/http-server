<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Abstracts\AbstractRequest;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Enums\HttpProtocol;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Commons\Url\UrlInfo;

/**
 * Represents an immutable server-side HTTP request.
 * Extends the base functionality of AbstractRequest to include additional properties specific to server requests,
 * such as the URL information and whether the connection is secure.
 * @property HeadersImmutable $headers
 */
final class ServerRequest extends AbstractRequest
{
    public function __construct(
        HttpMethod              $method,
        HttpProtocol            $protocol,
        HeadersImmutable        $headers,
        public readonly UrlInfo $url,
        public Buffer|\Closure  $body,
    )
    {
        parent::__construct($protocol, $method, $headers);
    }

    /**
     * @param HeadersImmutable $header
     * @return self
     */
    public function withHeaders(HeadersImmutable $header): self
    {
        return new self(
            $this->method,
            $this->protocol,
            $header,
            $this->url,
            $this->body,
        );
    }
}
