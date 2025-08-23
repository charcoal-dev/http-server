<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Http\Router\Controller\AbstractController;
use Charcoal\Http\Router\Middleware\MiddlewareBag;
use Charcoal\Http\Router\Router;
use Charcoal\Http\Router\Support\HttpMethods;

/**
 * Represents an HTTP Route configuration.
 * This class is immutable and defines a route's path, supported HTTP methods, and middleware pipelines.
 */
final readonly class Route
{
    public string $path;
    /** @var class-string<AbstractController> */
    public string $classname;
    /** @var array<non-empty-string,true> */
    public array $methods;
    /** @var ?array<string> */
    public ?array $middleware;

    public function __construct(
        public string $uniqueId,
        string        $path,
        string        $classname,
        ?HttpMethods  $methods,
        MiddlewareBag $middleware,
    )
    {
        $path = trim($path, "/");
        $this->path = match (true) {
            $path === "" => "/",
            (bool)preg_match('/^(?:[A-Za-z0-9_.-]*[A-Za-z0-9]|:[A-Za-z0-9]+)(?:\/(?:[A-Za-z0-9_.-]*[A-Za-z0-9]|:[A-Za-z0-9]+))*$/', $path) => $path,
            default => throw new \InvalidArgumentException("Route prefix is invalid: " . $path),
        };

        if (Router::$checkControllerExists) {
            if (!class_exists($classname) ||
                !is_subclass_of($classname, AbstractController::class, true)) {
                throw new \InvalidArgumentException("Controller class does not exist or is not a subclass of " .
                    AbstractController::class);
            }
        } else {
            if (!ObjectHelper::isValidClassname($classname)) {
                throw new \InvalidArgumentException("Controller class is invalid");
            }
        }

        // Canonicalize methods
        $methods = array_unique(array_map(fn($m) => $m->name, $methods?->getArray() ?? []));
        sort($methods, SORT_STRING);
        $this->methods = array_fill_keys($methods, true);
        $this->classname = $classname;
        $this->middleware = $middleware->all() ?: null;
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return [$this->path, $this->classname, $this->methods];
    }
}
