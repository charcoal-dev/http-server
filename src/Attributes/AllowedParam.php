<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;

/**
 * Attribute class used to specify required parameters for methods.
 * This attribute can be applied to methods and is repeatable.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class AllowedParam implements ControllerAttributeInterface
{
    /** @var string[] */
    public array $params;

    /**
     * @param string[]|\UnitEnum[] $params
     */
    public function __construct(array $params)
    {
        $this->params = array_map(
            fn($p) => is_string($p) ? $p : $p->name,
            $params
        );
    }

    /**
     * @return \Closure
     */
    public function getBuilderFn(): \Closure
    {
        return fn(
            mixed        $current,
            AllowedParam $attrInstance
        ): array => array_merge($current, $attrInstance->params);
    }
}