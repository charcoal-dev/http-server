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

namespace Charcoal\Http\Router\Authorization;

use Charcoal\Http\Commons\Headers;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class AbstractAuthorization
 * @package Charcoal\Http\Router\Authorization
 */
abstract class AbstractAuthorization
{
    /** @var array */
    protected array $users = [];
    /** @var null|callable */
    protected $unauthorizedFn = null;

    use NoDumpTrait;
    use NotCloneableTrait;
    use NotSerializableTrait;

    /**
     * @param string $realm
     */
    public function __construct(protected readonly string $realm)
    {
    }

    /**
     * Registers a username/password for authorized user
     * @param string $username
     * @param string $password
     * @return $this
     */
    final public function user(string $username, string $password): static
    {
        $this->users[$username] = new AuthUser($username, $password);
        return $this;
    }

    /**
     * Set a custom callback function when authorization fails
     * @param callable $callback
     * @return $this
     */
    final public function unauthorizedCallback(callable $callback): static
    {
        $this->unauthorizedFn = $callback;
        return $this;
    }

    /**
     * @param \Charcoal\Http\Commons\Headers $headers
     * @return void
     */
    abstract public function authorize(Headers $headers): void;

    /**
     * @param $in
     * @return string
     */
    protected function sanitizeValue($in): string
    {
        if (!is_string($in) || !$in) {
            return "";
        }

        return filter_var($in, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }
}
