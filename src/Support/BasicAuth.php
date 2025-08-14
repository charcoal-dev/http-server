<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Support;

use Charcoal\Http\Commons\Enums\AuthScheme;
use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Authorization\AbstractBasicAuth;
use Charcoal\Http\Router\Contracts\Auth\AuthContextInterface;
use Charcoal\Http\Router\Contracts\Auth\AuthRealmEnum;

/**
 * Class BasicAuth
 * @package Charcoal\Http\Router\Authorization
 */
class BasicAuth extends AbstractBasicAuth
{
    /**
     * @param AuthRealmEnum $realm
     * @param \Closure(string, string): bool $tryLogin
     * @param \Closure(AuthContextInterface): void|null $onSuccess
     * @param \Closure(WritableHeaders): bool|null $onFailure
     * @param AuthScheme $scheme
     */
    public function __construct(
        AuthRealmEnum             $realm,
        public readonly \Closure  $tryLogin,
        public readonly ?\Closure $onSuccess = null,
        public readonly ?\Closure $onFailure = null,
        AuthScheme                $scheme = AuthScheme::Basic
    )
    {
        parent::__construct($realm, $scheme);
    }

    protected function tryUserPassword(string $username, string $password): bool
    {
        return ($this->tryLogin)($username, $password);
    }


    public function onSuccess(): AuthContextInterface
    {
        $context = parent::onSuccess();
        if ($this->onSuccess) {
            ($this->onSuccess)($context);
        }

        return $context;
    }

    public function onFailure(WritableHeaders $headers): void
    {
        parent::onFailure($headers);
        if ($this->onFailure) {
            ($this->onFailure)($headers);
        }
    }
}