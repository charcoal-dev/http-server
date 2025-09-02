<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Snapshot;

use Charcoal\Http\Server\Internal\Constants;

/**
 * Represents inspection details of a route or route group.
 * This class provides metadata about a route or route group, including its
 * path, type, associated methods, and grouping namespace if applicable.
 */
final readonly class RouteSnapshot
{
    public string $matchRegExp;
    /** @var array<RouteControllerBinding> */
    public array $controllers;
    /** @var null|array<string> */
    public ?array $params;

    public function __construct(
        public string          $path,
        RouteControllerBinding ...$controllers
    )
    {
        $params = [];
        $matchRegExp = preg_quote($this->path, "~");
        $matchRegExp = preg_replace_callback(
            Constants::PARAM_NAME_CAPTURE_REGEXP,
            function ($match) use (&$params) {
                $params[] = $match[1];
                return Constants::PARAM_NAME_PLACEHOLDER;
            },
            $matchRegExp
        );

        $this->matchRegExp = "~^" . $matchRegExp . "$~";
        $this->params = $params ?: null;
        $this->controllers = $controllers;
    }

    /**
     * Aggregates methods from all controllers associated with the instance.
     */
    public function getAggregatedMethods(): array
    {
        $methods = [];
        foreach ($this->controllers as $controller) {
            if (is_array($controller->methods)) {
                $methods = [...$methods, ...array_map(fn($m) => strtolower($m->value), $controller->methods)];
            }
        }

        return array_unique($methods);
    }
}