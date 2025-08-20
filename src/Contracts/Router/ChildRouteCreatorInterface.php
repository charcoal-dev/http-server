<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Router;

/**
 * Interface ChildRouteCreatorInterface
 * @package Charcoal\Http\Router\Contracts\Router
 */
interface ChildRouteCreatorInterface
{
    public function route(string $prefix, string $namespaceOrClass): self;
}