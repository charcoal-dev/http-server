<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request\Result;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Router\Request\CorsPolicy;

/**
 * A representation of an HTTP OPTIONS response result,
 * specifically handling Cross-Origin Resource Sharing (CORS) headers.
 * This class is used to construct a response that includes specific CORS headers when a policy is provided.
 * The headers are set based on the passed CorsPolicy and allowed origin, following the CORS specifications.
 */
final readonly class OptionsResult extends SuccessResult
{
    public function __construct(
        int         $statusCode,
        ?string     $allowedOrigin,
        ?CorsPolicy $corsPolicy,
        Headers     $headers,
    )
    {
        if ($corsPolicy) {
            $headers->set("Access-Control-Allow-Origin", $allowedOrigin ?: "*");
            $headers->set("Access-Control-Allow-Methods", $corsPolicy->methods);
            $headers->set("Access-Control-Allow-Headers", $corsPolicy->allow);
            if ($corsPolicy->maxAge > 0) {
                $headers->set("Access-Control-Max-Age", strval($corsPolicy->maxAge));
            }

            if ($allowedOrigin && $corsPolicy->withCredentials) {
                $headers->set("Access-Control-Allow-Credentials", "true");
            }

            $headers->set("Cache-Control", "no-store");
        }

        parent::__construct($statusCode, $headers, null);
    }
}