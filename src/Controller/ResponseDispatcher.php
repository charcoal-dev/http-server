<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Controller;

use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Contracts\Response\ResponsePromisedOnDispatch;
use Charcoal\Http\Router\Exceptions\ResponseDispatchedException;

/**
 * Class ResponseDispatcher
 * @package Charcoal\Http\Router\Controller
 */
class ResponseDispatcher
{
    /**
     * @param FinalizedResponse $response
     * @return never
     * @throws ResponseDispatchedException
     */
    public static function dispatch(FinalizedResponse $response): never
    {
        // HTTP Response Code
        http_response_code($response->statusCode);

        // Content Type
        if ($response->contentType) {
            header("Content-Type: " . $response->contentType->value);
        }

        // Headers
        if ($response->headers->count()) {
            foreach ($response->headers->getArray() as $key => $val) {
                header(sprintf("%s: %s", $key, $val));
            }
        }

        // Body
        if ($response->body) {
            print($response->body->raw());
        }

        throw new ResponseDispatchedException(null);
    }

    /**
     * @param int $statusCode
     * @param WritableHeaders $headers
     * @param ResponsePromisedOnDispatch $promise
     * @return never
     * @throws ResponseDispatchedException
     */
    public static function dispatchPromise(
        int                       $statusCode,
        WritableHeaders           $headers,
        ResponsePromisedOnDispatch $promise
    ): never
    {
        // HTTP Response Code
        http_response_code($statusCode);

        // Headers
        if (!$headers->count()) {
            throw new \RuntimeException("Headers must be set before dispatching a promise");
        }

        foreach ($headers->getArray() as $key => $val) {
            header(sprintf("%s: %s", $key, $val));
        }

        // Throw with promise
        throw new ResponseDispatchedException($promise);
    }
}