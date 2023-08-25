<?php
/*
 * This file is a part of "charcoal-dev/http-router" package.
 * https://github.com/charcoal-dev/http-router
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/http-router/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\HTTP\Router\Authorization;

/**
 * Class AuthUser
 * @package Charcoal\HTTP\Router\Authorization
 */
class AuthUser
{
    /**
     * @param string $username
     * @param string $password
     */
    public function __construct(public readonly string $username, public readonly string $password)
    {
    }
}