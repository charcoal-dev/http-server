<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Support;

use Charcoal\Base\Dataset\BatchEnvelope;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Enums\HttpProtocol;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Server\Exceptions\Request\ResponseBytesDispatchedException;
use Charcoal\Http\Server\Request\Result\AbstractResult;
use Charcoal\Http\Server\Request\Result\ErrorResult;
use Charcoal\Http\Server\Request\Result\RedirectResult;
use Charcoal\Http\Server\Request\Result\SuccessResult;
use Charcoal\Http\Server\Request\ServerRequest;

/**
 * This class is useful for handling HTTP requests in server-side applications,
 * leveraging the context from PHP's global state.
 * @api
 */
abstract readonly class SapiRequest
{
    /**
     * Creates and returns a ServerRequest instance using global server variables.
     * Parses the HTTP protocol, method, request URI, headers, and determines
     * whether the connection is secure based on the values in the $_SERVER superglobal.
     * @throws WrappedException
     * @api
     */
    final public static function fromGlobals(): ServerRequest
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

        return new ServerRequest($method, $protocol, $headers, new UrlInfo($url), $isSecure, null, "php://input");
    }

    /**
     * This is an optional helper method, use this or implement your own logic following this.
     * Sends an HTTP response based on the provided result object, including status code,
     * headers, and response body. Handles redirects and errors as specific cases.
     * @throws ResponseBytesDispatchedException
     * @api
     */
    final public static function serveResult(AbstractResult $result): never
    {
        if (ob_get_level() > 0) {
            throw new \RuntimeException("Cannot send response while Output Buffering is enabled");
        }

        // Set the HTTP status code and headers
        http_response_code($result->statusCode);
        foreach ($result->headers as $name => $value) {
            header($name . ": " . $value);
        }

        // RedirectResult: Location header already served, Just terminate execution here
        if ($result instanceof RedirectResult) {
            exit(0);
        }

        // ErrorResult: Handle error cases
        if ($result instanceof ErrorResult) {
            self::handleErrorResult($result);
        }

        // SuccessResult: Send the response body
        assert($result instanceof SuccessResult);
        self::handleSuccessResult($result);
        exit(0); // Done!
    }

    /**
     * Handle success result by printing the body.
     */
    protected static function handleSuccessResult(SuccessResult $result): void
    {
        try {
            $result->response->send();
        } catch (ResponseBytesDispatchedException) {
        }
    }

    /**
     * Handles an ErrorResult by throwing the exception if present, or exiting the script.
     * @api Extend this method to handle success cases.
     */
    protected static function handleErrorResult(ErrorResult $result): never
    {
        if ($result->exception) {
            throw $result->exception;
        }

        exit(0);
    }
}