<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

/**
 * Represents a set of attributes that can be used to define behavior and constraints
 * for a controller in an application.
 */
enum ControllerAttribute
{
    case allowedParams;
    case rejectUnrecognizedParams;
    case constraints;
    case disableRequestBody;
    case allowFileUpload;
    case allowTextBody;
}