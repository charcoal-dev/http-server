<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

use Charcoal\Http\Commons\Abstracts\AbstractRequest;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Enums\HttpProtocol;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Commons\Url\UrlInfo;

/**
 * Class Request
 * @package Charcoal\Http\Router\Route
 */
final class ServerRequest extends AbstractRequest
{
    public function __construct(
        HttpMethod              $method,
        HttpProtocol            $protocol,
        HeadersImmutable        $headers,
        public readonly UrlInfo $url,
        public readonly bool    $isSecure,
    )
    {
        parent::__construct($protocol, $method, $headers);
    }
}
