<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;

/**
 * An attribute that can be applied to a class or method to ENABLE request body.
 * Request body by default is enabled; This attribute is useful if a class/parent overrides default behavior.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class EnableRequestBody implements ControllerAttributeInterface
{
    /**
     * @return \Closure
     */
    public function getBuilderFn(): \Closure
    {
        return fn(mixed $current, EnableRequestBody $attrInstance): bool => true;
    }
}