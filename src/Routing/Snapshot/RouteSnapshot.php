<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Snapshot;

use Charcoal\Http\Router\Controllers\ControllerValidated;
use Charcoal\Http\Router\Internal\Constants;

/**
 * Represents inspection details of a route or route group.
 * This class provides metadata about a route or route group, including its
 * path, type, associated methods, and grouping namespace if applicable.
 */
final readonly class RouteSnapshot
{
    public string $matchRegExp;
    /** @var array<ControllerBinding> */
    public array $controllers;
    /** @var null|array<string> */
    public ?array $params;

    public function __construct(
        public string       $path,
        ControllerValidated ...$controllers
    )
    {
        $params = [];
        $matchRegExp = preg_quote($this->path, "~");
        $matchRegExp = preg_replace_callback(
            Constants::PARAM_NAME_CAPTURE_REGEXP,
            function ($match) use (&$params) {
                $params[] = $match[1];
                return "([^/]+)";
            },
            $matchRegExp
        );

        $this->matchRegExp = "~^" . $matchRegExp . "$~";
        $this->params = $params ?: null;
        $this->controllers = $controllers;
    }
}