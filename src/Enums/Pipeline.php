<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

use Charcoal\Http\Server\Contracts\Middleware\ControllerGatewayFacadePipeline;
use Charcoal\Http\Server\Contracts\Middleware\RequestHeadersPipeline;
use Charcoal\Http\Server\Contracts\Middleware\UrlValidatorPipeline;

/**
 * This enum provides a set of predefined constants representing
 * specific interfaces used to handle various stages or concerns
 * within the application's kernel pipeline.
 */
enum Pipeline: string
{
    case URL_Validator = UrlValidatorPipeline::class;
    case Request_HeadersValidator = RequestHeadersPipeline::class;
    case Controller_ContextFacadeResolver = ControllerGatewayFacadePipeline::class;
}