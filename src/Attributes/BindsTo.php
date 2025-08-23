<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Attributes;

/**
 * An attribute class that binds a class to a specified contract.
 * This attribute is used to associate a class with an interface or abstract class that it implements or extends.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final readonly class BindsTo
{
    /** @param class-string $contract */
    public function __construct(
        public string $contract,
    )
    {
    }
}