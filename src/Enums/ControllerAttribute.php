<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

use Charcoal\Http\Server\Attributes\AllowedParam;
use Charcoal\Http\Server\Attributes\AllowFileUpload;
use Charcoal\Http\Server\Attributes\AllowTextBody;
use Charcoal\Http\Server\Attributes\CacheControlAttribute;
use Charcoal\Http\Server\Attributes\DefaultEntrypoint;
use Charcoal\Http\Server\Attributes\DisableRequestBody;
use Charcoal\Http\Server\Attributes\RejectUnrecognizedParams;

/**
 * Represents a set of attributes that can be used to define behavior and constraints
 * for a controller in an application.
 */
enum ControllerAttribute: string
{
    case defaultEntrypoint = DefaultEntrypoint::class;
    case allowedParams = AllowedParam::class;
    case rejectUnrecognizedParams = RejectUnrecognizedParams::class;
    case constraints = RequestConstraint::class;
    case disableRequestBody = DisableRequestBody::class;
    case allowFileUpload = AllowFileUpload::class;
    case allowTextBody = AllowTextBody::class;
    case cacheControl = CacheControlAttribute::class;
}