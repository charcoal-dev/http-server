<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Support;

use Charcoal\Http\Router\Contracts\Auth\AuthRealmEnum;

/**
 * Class AuthRealm
 * @package Charcoal\Http\Router\Support
 */
readonly class AuthRealm implements AuthRealmEnum
{
    public function __construct(public string $name)
    {
    }

    public function getRealmName(): string
    {
        return $this->name;
    }
}