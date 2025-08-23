<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

use Charcoal\Http\Router\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Router\Middleware\Bag\SealedBag;
use Charcoal\Http\Router\Support\HttpMethods;

/**
 * Represents an HTTP Route configuration.
 * This class is immutable and defines a route's path, supported HTTP methods, and middleware pipelines.
 */
final readonly class Route
{
    public string $path;
    /** @var class-string<ControllerInterface> */
    public string $classname;
    /** @var SealedBag */
    public SealedBag $middleware;

    public function __construct(
        string              $path,
        string              $classname,
        public ?HttpMethods $methods,
    )
    {
        $path = trim($path, "/");
        $this->path = match (true) {
            $path === "" => "/",
            (bool)preg_match('/^(?:[A-Za-z0-9_.-]*[A-Za-z0-9]|:[A-Za-z0-9]+)(?:\/(?:[A-Za-z0-9_.-]*[A-Za-z0-9]|:[A-Za-z0-9]+))*$/', $path) => $path,
            default => throw new \InvalidArgumentException("Route prefix is invalid: " . $path),
        };

        $this->classname = $classname;
    }

    /**
     * Sets the route's sealed middleware bag, wired internally
     * (This is a readonly class)
     * @internal
     */
    public function finalize(SealedBag $bag): void
    {
        $this->middleware = $bag;
    }
}
