<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Authorization;

use Charcoal\Http\Commons\Header\Headers;
use Charcoal\Http\Router\Exception\RouteAuthException;

/**
 * Class BasicAuth
 * @package Charcoal\Http\Router\Authorization
 */
class BasicAuth extends AbstractAuthorization
{
    /**
     * @param Headers $headers
     * @return void
     * @throws RouteAuthException
     */
    public function authorize(Headers $headers): void
    {
        try {
            $username = null;
            $password = null;

            $authorization = $headers->get("authorization");
            if ($authorization) {
                $authorization = explode(" ", $authorization);
                if (strtolower($authorization[0]) !== "basic") {
                    throw new RouteAuthException(
                        sprintf('Realm "%s" requires Basic auth, Invalid authorization header', $this->realm)
                    );
                }

                $credentials = base64_decode($authorization[1]);
                if (!$credentials) {
                    throw new RouteAuthException('Invalid Basic authorization header');
                }

                $credentials = explode(":", $credentials);
                $username = $this->sanitizeValue($credentials[0] ?? null);
                $password = $this->sanitizeValue($credentials[1] ?? null);
                unset($credentials);
            }

            // Sent username?
            if (!$username) {
                throw new RouteAuthException(
                    sprintf('Authentication is required to enter "%s"', $this->realm)
                );
            }

            // Authenticate
            try {
                /** @var null|AuthUser $user */
                $user = $this->users[$username] ?? null;
                if (!$user) {
                    throw new RouteAuthException('No such username was found');
                }

                if ($user->password !== $password) {
                    throw new RouteAuthException('Incorrect password');
                }
            } catch (RouteAuthException) {
                throw new RouteAuthException('Incorrect username or password');
            }
        } catch (RouteAuthException $e) {
            header(sprintf('WWW-Authenticate: Basic realm="%s"', $this->realm));
            header('HTTP/1.0 401 Unauthorized');

            // Callback method for unauthorized requests
            if (is_callable($this->unauthorizedFn)) {
                call_user_func($this->unauthorizedFn);
            }

            throw new RouteAuthException($e->getMessage());
        }
    }
}