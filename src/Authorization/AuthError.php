<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Authorization;

/**
 * Class AuthError
 * @package Charcoal\Http\Router\Authorization
 */
enum AuthError: int
{
    case INVALID_SCHEME = 1;
    case NO_SCHEME_CREDENTIALS = 2;
    case NO_CREDENTIALS = 11;
    case INVALID_CREDENTIALS = 12;
    case BAD_CREDENTIALS = 13;
}