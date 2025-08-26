<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

use Charcoal\Http\Server\Contracts\Pipelines\RequestIdResolverInterface;

/**
 * This enum provides a set of predefined constants representing
 * specific interfaces used to handle various stages or concerns
 * within the application's kernel pipeline.
 */
enum Pipelines: string
{
    case RequestID_Resolver = RequestIDResolverInterface::class;
    case URL_EncodingEnforcer = 2;
    case CORS_PolicyResolver = 3;
    case RequestBodyDecoder = 4;
    case ResponseBodyEncoder = 5;
    case ControllerContextResolver = 6;
}