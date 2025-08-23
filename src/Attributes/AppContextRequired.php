<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Attributes;

use Charcoal\Http\Router\Contracts\Controllers\AppContextEnumInterface;

/**
 * An attribute used to enforce an application context requirement by the controller itself.
 * This attribute is intended to be applied to controllers to define a specific application context
 * required through the provided implementation of the AppContextEnumInterface.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AppContextRequired
{
    public function __construct(
        public AppContextEnumInterface $context
    )
    {
    }
}