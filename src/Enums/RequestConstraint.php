<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

/**
 * This enum provides a set of predefined constraints that can be enforced
 * on HTTP request parameters, payload, or data structures to ensure they
 * adhere to specific limits or conditions. Each case corresponds to a
 * distinct type of constraint.
 */
enum RequestConstraint
{
    case maxBodyBytes;
    case maxParams;
    case maxParamLength;
    case dtoMaxDepth;
}