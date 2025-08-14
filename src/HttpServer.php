<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router;

use Charcoal\Base\Enums\ExceptionAction;
use Charcoal\Base\Support\Data\BatchEnvelope;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Data\UrlInfo;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Header\Headers;
use Charcoal\Http\Router\Request\Request;

/**
 * Class HttpServer
 * @package Charcoal\Http\Router
 */
readonly class HttpServer
{
    /**
     * @param Router $router
     * @param \Closure $closure
     * @return void
     * @throws Exception\RoutingException
     * @throws \Charcoal\Base\Exceptions\WrappedException
     * @throws \Charcoal\Http\Commons\Exception\InvalidUrlException
     */
    public static function requestFromServerGlobals(
        Router   $router,
        \Closure $closure
    ): void
    {
        // Check if URL not rewritten properly (i.e., called /index.php/some/controller)
        $url = $_SERVER["REQUEST_URI"] ?? "";
        if (preg_match('/^\/?[\w\-.]+\.php\//', $url)) {
            $url = explode("/", $url);
            unset($url[1]);
            $url = implode("/", $url);
        }

        // Prerequisites
        $url = new UrlInfo($url);
        $method = HttpMethod::tryFrom($_SERVER["REQUEST_METHOD"] ?? "");
        if (!$method) {
            throw new \UnexpectedValueException('Unsupported HTTP method read from REQUEST_METHOD');
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

        $incomingLogger = $router->policy->incomingLogger;
        $headersPolicy = $router->policy->incomingHeaders;
        $headers = new Headers(
            $headersPolicy,
            $headersPolicy->keyPolicy,
            new BatchEnvelope(
                $headers,
                $incomingLogger?->onInvalidHeader() ?
                    ExceptionAction::Log : ExceptionAction::Throw,
                $incomingLogger?->onInvalidHeader()
            )
        );

        unset($headersPolicy);

        // Payload & Body
        $payload = [];
        $body = new Buffer();
        $contentType = ContentType::find($_SERVER["CONTENT_TYPE"] ?? "");

        // Ready query string
        if (isset($_SERVER["QUERY_STRING"])) {
            parse_str($_SERVER["QUERY_STRING"], $payload);
        }

        // Get input body from stream
        $params = [];
        $stream = file_get_contents("php://input");
        if ($stream) {
            if ($router->policy->parsePayloadKeepBody) {
                $body->append($stream);
            }

            switch ($contentType) {
                case ContentType::JSON:
                    try {
                        $json = json_decode($stream, true, flags: JSON_THROW_ON_ERROR);
                    } catch (\JsonException $e) {
                        throw new \RuntimeException("Failed to decode request body as JSON", previous: $e);
                    }

                    if (is_array($json)) {
                        $params = $json;
                    } elseif (is_scalar($json) || is_null($json)) {
                        $params = [$router->policy->parseScalarPayloadParam => $json];
                    }

                    break;
                case "application/x-www-form-urlencoded":
                    parse_str($stream, $params);
                    break;
                case "multipart/form-data":
                    if ($method === HttpMethod::POST) {
                        $params = $_POST;
                    }

                    break;
            }
        }

        if (is_array($params) && $params) {
            $payload = array_merge($params, $payload);
        }

        // Payload
        $payloadPolicy = $router->policy->incomingPayload;
        $payload = new UnsafePayload(
            $payloadPolicy,
            $payloadPolicy->keyPolicy,
            new BatchEnvelope(
                $payload,
                $incomingLogger?->onInvalidPayload() ?
                    ExceptionAction::Log : ExceptionAction::Throw,
                $incomingLogger?->onInvalidPayload()
            )
        );

        // Bypass HTTP auth.
        $bypassAuth = false;
        if ($method === HttpMethod::OPTIONS) {
            $bypassAuth = true;
        }

        // Get Controller
        $controller = $router->try(new Request($method, $url, $headers, $payload, $body), $bypassAuth);

        // Callback Close
        call_user_func($closure, $controller);
    }
}
