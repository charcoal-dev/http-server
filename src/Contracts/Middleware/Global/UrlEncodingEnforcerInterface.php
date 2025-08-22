<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Global;

use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Router\Contracts\Middleware\GlobalMiddlewareInterface;
use Charcoal\Http\Router\Exceptions\Middleware\Global\UrlEncodingViolation;

/**
 * This interface acts as middleware, ensuring that the provided URL complies
 * with specific encoding standards. If the URL does not meet the required
 * criteria, an exception may be thrown.
 */
interface UrlEncodingEnforcerInterface extends GlobalMiddlewareInterface
{
    /**
     * @throws UrlEncodingViolation
     */
    public function __invoke(UrlInfo $url): void;
}