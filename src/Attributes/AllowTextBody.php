<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AllowTextBody implements ControllerAttributeInterface
{
    /**
     * @return \Closure
     */
    public function getBuilderFn(): \Closure
    {
        return fn(mixed $current, AllowTextBody $attrInstance): bool => true;
    }
}