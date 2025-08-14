<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Auth;

/**
 * Interface AuthRealmInterface
 * @package Charcoal\Http\Router\Contracts
 */
interface AuthRealmEnum
{
    public function getRealmName(): string;
}