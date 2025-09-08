<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;

/**
 * Represents a class-level attribute for cache control directives.
 * Allows configuration of caching behavior when applied to a specific class.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class CacheControl implements ControllerAttributeInterface
{
    public function __construct(
        public CacheControlDirectives $cacheControl
    )
    {
        if (!$this->cacheControl->validate()) {
            throw new \InvalidArgumentException("Invalid cache control directives");
        }
    }

    /**
     * @return \Closure
     */
    public function getBuilderFn(): \Closure
    {
        return fn(
            mixed        $current,
            CacheControl $attrInstance
        ): CacheControlDirectives => $attrInstance->cacheControl;
    }
}