<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;

/**
 * An attribute that can be applied to a class or method to enable caching of responses.
 * This attribute is part of a mechanism for handling cached responses in a controller.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class EnableCachedResponse implements ControllerAttributeInterface
{
    /**
     * @return \Closure
     */
    public function getBuilderFn(): \Closure
    {
        return fn(mixed $current, EnableCachedResponse $attrInstance): bool => true;
    }
}