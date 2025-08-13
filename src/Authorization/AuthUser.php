<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Authorization;

/**
 * Class AuthUser
 * @package Charcoal\Http\Router\Authorization
 */
readonly class AuthUser
{
    public function __construct(
        public string $username,
        #[\SensitiveParameter]
        public string $password
    )
    {
    }
}