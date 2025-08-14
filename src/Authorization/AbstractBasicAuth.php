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
use Charcoal\Http\Router\Contracts\AuthContextInterface;
use Charcoal\Http\Router\Contracts\AuthenticatorInterface;
use Charcoal\Http\Router\Contracts\AuthRealmInterface;
use Charcoal\Http\Router\Exception\AuthorizationException;
use Charcoal\Http\Router\Request\Request;

/**
 * Class AbstractBasicAuth
 * @package Charcoal\Http\Router\Authorization
 */
abstract class AbstractBasicAuth extends AbstractAuthorization implements AuthenticatorInterface
{
    /**
     * @param AuthRealmInterface $realm
     * @param AuthScheme $scheme
     */
    public function __construct(
        AuthRealmInterface $realm,
        public AuthScheme  $scheme = AuthScheme::Basic
    )
    {
        parent::__construct($realm);
    }

    abstract protected function tryUserPassword(string $username, string $password): bool;

    /**
     * @param Request $request
     * @return bool
     * @throws AuthorizationException
     */
    public function authenticate(Request $request): bool
    {
        $scheme = $this->scheme->value;
        if (!is_string($scheme) || !$scheme) {
            throw new AuthorizationException(AuthError::NO_SCHEME_CREDENTIALS);
        }

        $credentials = $request->authorization()->schemes[$scheme] ?? null;
        if (!$credentials) {
            throw new AuthorizationException(AuthError::NO_SCHEME_CREDENTIALS);
        }

        $credentials = EncodingHelper::isBase64Encoded($credentials, lengthCheck: true) ?
            base64_decode($credentials) : null;
        if (!$credentials) {
            throw new AuthorizationException(AuthError::INVALID_CREDENTIALS);
        }

        $credentials = explode(":", $credentials);
        $username = $credentials[0] ?: null;
        $password = $credentials[1] ?: null;
        if (!$username || !$password) {
            throw new AuthorizationException(AuthError::NO_CREDENTIALS);
        }

        if (!$this->tryUserPassword($username, $password)) {
            throw new AuthorizationException(AuthError::BAD_CREDENTIALS);
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