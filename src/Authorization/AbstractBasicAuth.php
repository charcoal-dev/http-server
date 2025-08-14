<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Authorization;

use Charcoal\Base\Support\Helpers\EncodingHelper;
use Charcoal\Http\Commons\Enums\AuthScheme;
use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Contracts\Auth\AuthContextInterface;
use Charcoal\Http\Router\Contracts\Auth\AuthenticatorInterface;
use Charcoal\Http\Router\Contracts\Auth\AuthRealmEnum;
use Charcoal\Http\Router\Exception\AuthenticationException;
use Charcoal\Http\Router\Request\Request;

/**
 * Class AbstractBasicAuth
 * @package Charcoal\Http\Router\Authorization
 */
abstract class AbstractBasicAuth extends AbstractAuthorization implements AuthenticatorInterface
{
    /**
     * @param AuthRealmEnum $realm
     * @param AuthScheme $scheme
     */
    public function __construct(
        AuthRealmEnum     $realm,
        public AuthScheme $scheme = AuthScheme::Basic
    )
    {
        parent::__construct($realm);
    }

    abstract protected function tryUserPassword(string $username, string $password): bool;

    /**
     * @param Request $request
     * @return bool
     * @throws AuthenticationException
     */
    public function authenticate(Request $request): bool
    {
        $scheme = $this->scheme->value;
        if (!is_string($scheme) || !$scheme) {
            throw new AuthenticationException(AuthError::NO_SCHEME_CREDENTIALS);
        }

        $credentials = $request->authorization()->schemes[$scheme] ?? null;
        if (!$credentials) {
            throw new AuthenticationException(AuthError::NO_SCHEME_CREDENTIALS);
        }

        $credentials = EncodingHelper::isBase64Encoded($credentials, lengthCheck: true) ?
            base64_decode($credentials) : null;
        if (!$credentials) {
            throw new AuthenticationException(AuthError::INVALID_CREDENTIALS);
        }

        $credentials = explode(":", $credentials);
        $username = $credentials[0] ?: null;
        $password = $credentials[1] ?: null;
        if (!$username || !$password) {
            throw new AuthenticationException(AuthError::NO_CREDENTIALS);
        }

        if (!$this->tryUserPassword($username, $password)) {
            throw new AuthenticationException(AuthError::BAD_CREDENTIALS);
        }

        return true;
    }

    /**
     * @return AuthContextInterface
     */
    public function onSuccess(): AuthContextInterface
    {
        return new AuthContext(AuthScheme::Basic, $this->realm);
    }

    /**
     * @param WritableHeaders $headers
     * @return void
     */
    public function onFailure(WritableHeaders $headers): void
    {
        $headers->set("WWW-Authenticate", sprintf('Basic realm="%s"', $this->realm->getRealmName()));
    }
}