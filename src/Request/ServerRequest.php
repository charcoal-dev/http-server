<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

use Charcoal\Http\Commons\Abstracts\AbstractRequest;
use Charcoal\Http\Commons\Enums\ContentType;
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
    public readonly ?ContentType $contentType;

    public function __construct(
        HttpMethod              $method,
        HttpProtocol            $protocol,
        HeadersImmutable        $headers,
        public readonly UrlInfo $url,
        public readonly bool    $isSecure,
    )
    {
        parent::__construct($protocol, $method, $headers);
        $this->contentType = ContentType::find($headers->get("Content-Type") ?? "");
    }
}
