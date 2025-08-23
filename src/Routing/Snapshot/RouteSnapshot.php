<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Routing\Snapshot;

use Charcoal\Http\Router\Controller\AbstractController;
use Charcoal\Http\Router\Routing\Group\AbstractRouteGroup;
use Charcoal\Http\Router\Routing\Route;

/**
 * Represents inspection details of a route or route group.
 * This class provides metadata about a route or route group, including its
 * path, type, associated methods, and grouping namespace if applicable.
 */
final readonly class RouteSnapshot
{
    public bool $isGroup;
    public bool $isController;
    /** @var null|array<string,class-string<AbstractController>> */
    public ?array $methods;
    public string $matchRegExp;
    /** @var null|array<string,string> */
    public ?array $params;

    public function __construct(public int $index, public string $path, array $node)
    {
        $isGroup = false;
        $isRoute = false;
        $methods = [];
        foreach ($node as $leaf) {
            if ($leaf instanceof AbstractRouteGroup) {
                $isGroup = true;
                continue;
            }

            if ($leaf instanceof Route) {
                $isRoute = true;
                foreach ($leaf->methods ?: ["*" => true] as $method => $bool) {
                    $methods[$method] = $leaf->classname;
                }
            }
        }

        $this->isGroup = $isGroup;
        $this->isController = $isRoute;
        $this->methods = $methods ?: null;

        $params = [];
        $matchRegExp = preg_quote($this->path, "~");
        $matchRegExp = preg_replace_callback(
            "/\\\\:([A-Za-z0-9_]+)/",
            function ($match) use (&$params) {
                $params[] = $match[1];
                return "([^/]+)";
            },
            $matchRegExp
        );

        $this->matchRegExp = "~^" . $matchRegExp . "$~";
        $this->params = $params ?: null;
    }
}