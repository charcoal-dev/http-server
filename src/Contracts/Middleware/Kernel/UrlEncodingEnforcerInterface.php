<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Middleware\Kernel;

use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Router\Contracts\Middleware\Factory\MiddlewareConstructableInterface;
use Charcoal\Http\Router\Request\Result\RedirectUrl;

/**
 * Interface that defines a contract for enforcing proper URL encoding rules.
 * This ensures that URLs meet specific encoding standards and can optionally
 * provide a redirection URL if a change is required.
 */
interface UrlEncodingEnforcerInterface extends KernelMiddlewareInterface, MiddlewareConstructableInterface
{
    public function __invoke(UrlInfo $url): ?RedirectUrl;
}