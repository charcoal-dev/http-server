<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Registry;

use Charcoal\Http\Commons\Support\HttpMethods;
use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Server\Internal\Constants;

/**
 * Represents an HTTP Route configuration.
 * This class is immutable and defines a route's path, supported HTTP methods, and middleware pipelines.
 */
final readonly class Route
{
    public string $path;
    /** @var class-string<ControllerInterface> */
    public string $classname;

    public function __construct(
        string              $path,
        string              $classname,
        public ?HttpMethods $methods,
    )
    {
        $path = trim($path, "/");
        $this->path = match (true) {
            $path === "" => "/",
            (bool)preg_match(Constants::PATH_VALIDATION_REGEXP, $path) => $path,
            default => throw new \InvalidArgumentException("Route prefix is invalid: " . $path),
        };

        $this->classname = $classname;
    }
}
