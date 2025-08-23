<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Exceptions\Middleware;

use Charcoal\Http\Router\Enums\Middleware\Scope;

/**
 * Class MiddlewareResolverException
 * @package Charcoal\Http\Router\Exceptions\Middleware
 */
final class MiddlewareResolverException extends \RuntimeException
{
    public function __construct(
        public readonly Scope  $scope,
        public readonly string $contract,
        public array           $context = [],
        string                 $message = "Could not resolve required middleware",
        \Throwable             $previous = null
    )
    {
        parent::__construct($message, previous: $previous);
    }
}