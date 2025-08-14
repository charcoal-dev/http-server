<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Enums;

/**
 * Class CacheStoreDirective
 * @package Charcoal\Http\Router\Enums
 */
enum CacheStoreDirective: string
{
    case PUBLIC = "public";
    case PRIVATE = "private";
    case NO_STORE = "no-store";
}
