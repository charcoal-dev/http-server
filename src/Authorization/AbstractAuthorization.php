<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Authorization;

use Charcoal\Base\Charsets\Ascii;
use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Http\Commons\Header\Headers;

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
     * @param Headers $headers
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

        return Ascii::sanitizeUseFilter($in);
    }
}
