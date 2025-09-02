<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Server\Routing\Snapshot\RouteControllerBinding;
use Charcoal\Http\Server\Routing\Snapshot\RouteSnapshot;
use Charcoal\Http\Server\Routing\Snapshot\RoutingSnapshot;

/**
 * Represents a router responsible for matching paths against predefined route patterns.
 * Uses a snapshot of app routes during initialization to perform the matching logic.
 */
final readonly class Router
{
    /** @internal */
    public function __construct(private RoutingSnapshot $snapshot)
    {
    }

    /**
     * Matches a given path against the route patterns in the snapshot
     * and returns the matching route and extracted tokens if a match is found.
     * @return array{RouteSnapshot, array<string>}|false
     */
    public function match(string $path): array|false
    {
        $normalized = "/" . trim($path, "/");
        foreach ($this->snapshot as $route) {
            $tokens = [];
            if (preg_match_all($route->matchRegExp, $normalized, $tokens) === 1) {
                if ($tokens) unset($tokens[0]);
                return [$route, $tokens];
            }
        }

        return false;
    }

    /**
     * Resolves and returns the appropriate controller and its matching entry point
     * for a given route and HTTP method. If no matching controller is found or if
     * the method is not declared, an exception is thrown.
     * @param RouteSnapshot $route
     * @param HttpMethod $method
     * @return array{RouteControllerBinding, string|null}|null
     */
    public function declaredControllersFor(RouteSnapshot $route, HttpMethod $method): ?array
    {
        $defaultController = null;
        $matchedController = null;
        foreach ($route->controllers as $controller) {
            if ($controller->methods === true) {
                $defaultController = $controller;
                continue;
            }

            assert(is_array($controller->methods));
            if (in_array($method, $controller->methods)) {
                $matchedController = $controller;
                break;
            }
        }

        $controller = $matchedController ?? $defaultController ?? null;
        if (!$controller) {
            throw new \RuntimeException("No controller resolved with HTTP method: " . $method->value);
        }

        $entryPoint = $controller->matchEntryPoint($method);
        return [$controller, $entryPoint];
    }
}