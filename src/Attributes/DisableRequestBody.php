<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

/**
 * This attribute is used to indicate that the request body should be disabled for the specified controller.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class DisableRequestBody
{
}