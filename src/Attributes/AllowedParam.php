<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

/**
 * Attribute class used to specify required parameters for methods.
 * This attribute can be applied to methods and is repeatable.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class AllowedParam
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
}