<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Pipelines;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Support\CorsPolicy;
use Charcoal\Http\Server\Contracts\Middleware\OptionsMethodHandlerPipeline;
use Charcoal\Http\Server\Exceptions\PreFlightTerminateException;

/**
 * This class is responsible for processing CORS (Cross-Origin Resource Sharing) preflight
 * requests and updating the response headers accordingly based on the provided
 * CORS policy configuration. It validates the origin, methods, and headers, and ensures
 * compliance with the defined CORS rules.
 *
 * Throws exceptions to terminate the request when appropriate, either allowing all origins
 * or enforcing strict CORS validation.
 */
final class OptionsMethodHandler implements OptionsMethodHandlerPipeline
{
    public static bool $echoAcceptedOriginOnly = false;

    /**
     * @throws PreFlightTerminateException
     */
    public function __invoke(string $origin, CorsPolicy $corsPolicy, Headers $response): void
    {
        if (!$corsPolicy->enforce) {
            // Effectively Allow All
            $response->set("Access-Control-Allow-Origin", "*");
            throw new PreFlightTerminateException(true);
        }

        // Validate Origin against the approved
        if (!in_array(strtolower($origin), $corsPolicy->origins, true)) {
            // Failed CORS Policy
            throw new PreFlightTerminateException(false);
        }

        $response->set("Access-Control-Allow-Origin", self::$echoAcceptedOriginOnly ? $origin : "*");
        $response->set("Access-Control-Allow-Methods", $corsPolicy->methods);
        $response->set("Access-Control-Allow-Headers", $corsPolicy->allow);
        if ($corsPolicy->maxAge > 0) {
            $response->set("Access-Control-Max-Age", strval($corsPolicy->maxAge));
        }

        if (self::$echoAcceptedOriginOnly && $corsPolicy->withCredentials) {
            $response->set("Access-Control-Allow-Credentials", "true");
        }

        $response->set("Cache-Control", "no-store");

        // And terminate the request:
        throw new PreFlightTerminateException(true);
    }

    /**
     * @throws PreFlightTerminateException
     */
    public function execute(array $params): null
    {
        $this->__invoke(...$params);
    }
}