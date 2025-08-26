<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Middleware;

use Charcoal\Http\Commons\Enums\HeaderKeyValidation;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Commons\Support\CorsPolicy;
use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Server\Enums\Pipeline;
use Charcoal\Http\Server\Exceptions\PreFlightTerminateException;
use Charcoal\Http\Server\Pipelines\OptionsMethodHandler;
use Charcoal\Http\Server\Pipelines\RequestHeadersValidator;
use Charcoal\Http\Server\Pipelines\UrlValidator;
use Charcoal\Http\Server\Request\Result\RedirectUrl;

/**
 * Represents a middleware facade responsible for managing and executing middleware pipelines.
 * Provides a structured mechanism to handle middleware execution for various workflows.
 */
final readonly class MiddlewareFacade
{
    public function __construct(private MiddlewareRegistry $registry)
    {
    }

    /**
     * Executes the URL validation pipeline with the specified URL and constraints.
     */
    public function urlValidationPipeline(UrlInfo $url, int $maxUriBytes): ?RedirectUrl
    {
        return $this->registry->execute(Pipeline::URL_Validator,
            UrlValidator::class,
            [$url, $maxUriBytes]
        );
    }

    /**
     * Executes the header validation pipeline with the specified headers,
     * constraints, and key validation rules.
     */
    public function headerValidationPipeline(
        HeadersImmutable    $headers,
        int                 $maxHeaders,
        int                 $maxHeaderLength,
        HeaderKeyValidation $keyValidation
    ): HeadersImmutable
    {
        return $this->registry->execute(Pipeline::RequestHeaders_Validator,
            RequestHeadersValidator::class,
            [$headers, $maxHeaders, $maxHeaderLength, $keyValidation]
        );
    }

    /**
     * Handles the HTTP OPTIONS method by resolving CORS policies and
     * setting the appropriate response headers.
     * @see PreFlightTerminateException
     */
    public function optionsMethodHandler(string $origin, CorsPolicy $corsPolicy, Headers $response): void
    {
        $this->registry->execute(Pipeline::Options_MethodResolver,
            OptionsMethodHandler::class,
            [$origin, $corsPolicy, $response]
        );
    }
}