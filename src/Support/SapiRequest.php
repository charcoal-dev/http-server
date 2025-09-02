<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Support;

use Charcoal\Base\Abstracts\Dataset\BatchEnvelope;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Enums\HttpProtocol;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Server\Request\ServerRequest;

/**
 * This class is useful for handling HTTP requests in server-side applications,
 * leveraging the context from PHP's global state.
 */
abstract class SapiRequest
{
    /**
     * Creates and returns a ServerRequest instance using global server variables.
     * Parses the HTTP protocol, method, request URI, headers, and determines
     * whether the connection is secure based on the values in the $_SERVER superglobal.
     */
    public static function fromGlobals(): ServerRequest
    {
        // Server Protocol
        $protocol = HttpProtocol::find($_SERVER["SERVER_PROTOCOL"] ?? "");
        if (!$protocol) {
            throw new \UnexpectedValueException("Unsupported HTTP protocol read from SERVER_PROTOCOL");
        }

        // Request Method
        $method = HttpMethod::tryFrom($_SERVER["REQUEST_METHOD"] ?? "");
        if (!$method) {
            throw new \UnexpectedValueException("Unsupported HTTP method read from REQUEST_METHOD");
        }

        // Check if URL not rewritten properly (i.e., called /index.php/some/controller)
        $url = $_SERVER["REQUEST_URI"] ?? "";
        if (preg_match('/^\/?[\w\-.]+\.php\//', $url)) {
            $url = explode("/", $url);
            unset($url[1]);
            $url = implode("/", $url);
        }

        // Headers
        $headers = []; // Initiate Headers
        foreach ($_SERVER as $key => $value) {
            $key = explode("_", $key);
            if ($key[0] === "HTTP") {
                unset($key[0]);
                $key = array_map(function ($part) {
                    return ucfirst(strtolower($part));
                }, $key);

                $headers[implode("-", $key)] = $value;
            }
        }

        $headers = (new Headers(new BatchEnvelope($headers)))->toImmutable();
        $isSecure = match (strtolower($_SERVER["HTTPS"] ?? "")) {
            "on", "1" => true,
            "off" => false,
            default => ($_SERVER["SERVER_PORT"] ?? -1) === 443 ||
                ($_SERVER["REQUEST_SCHEME"] ?? "http") === "https",
        };

        return new ServerRequest($method, $protocol, $headers, new UrlInfo($url), $isSecure);
    }
}