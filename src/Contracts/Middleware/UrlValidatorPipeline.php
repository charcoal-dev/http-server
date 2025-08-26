<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Middleware;

use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Server\Config\RequestConstraints;
use Charcoal\Http\Server\Request\Result\RedirectUrl;

/**
 * Represents an interface for validating URLs.
 * This interface extends the PipelineInterface and provides a method
 * for validating or processing a given URL represented by the UrlInfo object
 * and potentially returning a RedirectUrl instance if applicable.
 */
interface UrlValidatorPipeline extends PipelineMiddlewareInterface
{
    public function __invoke(UrlInfo $url, RequestConstraints $constraints): ?RedirectUrl;
}