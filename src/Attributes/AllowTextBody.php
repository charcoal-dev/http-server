<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;

/**
 * Represents an attribute that allows a class to handle a text body in a specific manner.
 * Text bodies (contentTypes: text/plain, text/html, text/css, etc...) are disabled by default.
 * Special use-cases only; This does NOT include application/* bodies (i.e., JSON, XML, etc...)
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
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