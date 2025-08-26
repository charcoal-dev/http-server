<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Middleware;

use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Server\Config\RequestConstraints;
use Charcoal\Http\Server\Contracts\Middleware\UrlValidatorPipeline;
use Charcoal\Http\Server\Enums\Pipeline;
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
     * @param UrlInfo $url
     * @param RequestConstraints $constraints
     * @return RedirectUrl|null
     * @see UrlValidatorPipeline
     */
    public function urlValidationPipeline(UrlInfo $url, RequestConstraints $constraints): ?RedirectUrl
    {
        return $this->registry->execute(Pipeline::URL_Validator,
            UrlValidator::class,
            [$url, $constraints]
        );
    }
}