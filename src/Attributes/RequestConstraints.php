<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Server\Enums\RequestConstraint;

/**
 * Represents an override for a request constraint.
 * This attribute allows the customization of specific request constraints
 * by setting a custom value. The value must be within the range of
 * 0 to 0xFFFFFFFF; otherwise, an exception will be thrown.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final readonly class RequestConstraints
{
    public function __construct(
        public RequestConstraint $constraint,
        public int               $value,
    )
    {
        if ($value < 0 || $value > 0xFFFFFFFF) {
            throw new \InvalidArgumentException(
                "Invalid value for request constraint " . $constraint->name . ": " . $value
            );
        }
    }
}