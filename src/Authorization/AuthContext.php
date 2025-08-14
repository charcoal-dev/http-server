<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Authorization;

use Charcoal\Http\Commons\Enums\AuthScheme;
use Charcoal\Http\Router\Contracts\AuthContextInterface;
use Charcoal\Http\Router\Contracts\AuthRealmInterface;

/**
 * Class AuthContext
 * @package Charcoal\Http\Router\Authorization
 */
readonly class AuthContext implements AuthContextInterface
{
    public function __construct(
        public AuthScheme         $scheme,
        public AuthRealmInterface $realm
    )
    {
    }
}