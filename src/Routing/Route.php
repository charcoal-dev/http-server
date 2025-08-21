<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing;

use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Http\Router\Controller\AbstractController;
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

    public function __construct(
        string       $path,
        string       $classname,
        ?HttpMethods $methods,
        bool         $checkClass = true
    )
    {
        $path = "/" . trim(strtolower($path), "/");
        if (!preg_match('/^(\/[A-Za-z0-9_\-.]*[A-Za-z0-9]|\/:[A-Za-z0-9]+)+$/', $path)) {
            throw new \InvalidArgumentException("Route prefix is invalid");
        }

        if ($checkClass) {
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

        $this->path = $path;
        $this->classname = $classname;
        $this->methods = array_fill_keys(array_map(fn($m) => $m->name, $methods?->getArray() ?? []), true);
    }
}
