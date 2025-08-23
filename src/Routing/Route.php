<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Http\Router\Contracts\PathHolderInterface;
use Charcoal\Http\Router\Controller\AbstractController;
use Charcoal\Http\Router\Middleware\Bag\SealedBag;
use Charcoal\Http\Router\Router;
use Charcoal\Http\Router\Support\HttpMethods;

/**
 * Represents an HTTP Route configuration.
 * This class is immutable and defines a route's path, supported HTTP methods, and middleware pipelines.
 */
final readonly class Route implements PathHolderInterface
{
    public string $uniqueId;
    public string $path;
    /** @var class-string<AbstractController> */
    public string $classname;
    /** @var array<non-empty-string,true> */
    public array $methods;
    /** @var SealedBag */
    public SealedBag $middleware;

    public function __construct(
        string       $path,
        string       $classname,
        ?HttpMethods $methods,
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
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return [$this->path, $this->classname, $this->methods];
    }

    /**
     * Sets the unique identifier. (This is a readonly class)
     * @internal
     */
    public function finalize(SealedBag $bag): void
    {
        $this->uniqueId = $bag->owner;
        $this->middleware = $bag;
    }

    /**
     * Retrieves the unique identifier.
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * @return SealedBag
     */
    public function pipelines(): SealedBag
    {
        return $this->middleware;
    }
}
