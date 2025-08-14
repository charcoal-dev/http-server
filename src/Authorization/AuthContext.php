<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Authorization;

use Charcoal\Http\Commons\Enums\AuthScheme;
use Charcoal\Http\Router\Contracts\Auth\AuthContextInterface;
use Charcoal\Http\Router\Contracts\Auth\AuthRealmEnum;

/**
 * Class AuthContext
 * @package Charcoal\Http\Router\Authorization
 */
readonly class AuthContext implements AuthContextInterface
{
    public function __construct(
        public AuthScheme         $scheme,
        public AuthRealmEnum $realm
    )
    {
    }
}