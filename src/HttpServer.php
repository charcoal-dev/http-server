<?php
/*
 * This file is a part of "charcoal-dev/http-router" package.
 * https://github.com/charcoal-dev/http-router
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/http-router/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Http\Router;

use Charcoal\Base\Enums\ExceptionAction;
use Charcoal\Base\Support\Data\BatchEnvelope;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Data\UrlInfo;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Header\Headers;
use Charcoal\Http\Router\Controllers\Request;

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
     * @throws Exception\RouterException
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
                $incomingLogger?->handlesInvalidHeader() ? ExceptionAction::Log : ExceptionAction::Throw,
                $incomingLogger?->handlesInvalidHeader() ? $incomingLogger->onInvalidHeader() : null
            )
        );

        unset($headersPolicy);

        // Payload & Body
        $body = new Buffer();
        $payload = []; // Initiate payload
        $contentType = strtolower(trim(explode(";", $_SERVER["CONTENT_TYPE"] ?? "")[0]));

        // Ready query string
        if (isset($_SERVER["QUERY_STRING"])) {
            parse_str($_SERVER["QUERY_STRING"], $payload);
        }

        // Get input body from stream
        $params = [];
        $stream = file_get_contents("php://input");
        if ($stream) {
            $body->append($stream); // Append "as-is" (unsanitized) body to request
            switch ($contentType) {
                case "application/json":
                    try {
                        $json = json_decode($stream, true, flags: JSON_THROW_ON_ERROR);
                    } catch (\JsonException $e) {
                        throw new \RuntimeException('Failed to decode request body as JSON', previous: $e);
                    }

                    if (is_array($json)) {
                        $params = $json;
                    } elseif (is_scalar($json) || is_null($json)) {
                        $params = ["_json" => $json];
                    }

                    break;
                case "application/x-www-form-urlencoded":
                    parse_str($stream, $params);
                    break;
                case "multipart/form-data":
                    if ($method === HttpMethod::POST) {
                        $params = $_POST; // Simply use $_POST var;
                    }

                    break;
            }
        }

        if (is_array($params) && $params) { // Merge body and URL params
            $payload = array_merge($params, $payload);
        }

        // Payload
        $payloadPolicy = $router->policy->incomingPayload;
        $payload = new UnsafePayload(
            $payloadPolicy,
            $payloadPolicy->keyPolicy,
            new BatchEnvelope(
                $payload,
                $incomingLogger?->handlesInvalidPayload() ? ExceptionAction::Log : ExceptionAction::Throw,
                $incomingLogger?->handlesInvalidPayload() ? $incomingLogger->onInvalidPayload() : null
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
