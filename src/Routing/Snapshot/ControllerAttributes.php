<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Snapshot;

use Charcoal\Base\Arrays\ArrayHelper;
use Charcoal\Http\Server\Enums\ControllerAttribute;

/**
 * Represents metadata and configuration attributes for a controller.
 * This class provides functionality for handling allowed parameters
 * and rejecting unrecognized parameters based on reflection data
 * retrieved from controller attributes.
 */
final readonly class ControllerAttributes
{
    public ?string $defaultEntrypoint;

    public function __construct(
        public string $classname,
        public ?array $entryPoints,
        public array  $attributes,
        public bool   $validated
    )
    {
        $this->defaultEntrypoint = $this->attributes[ControllerAttribute::defaultEntrypoint->name] ?? null;
    }

    /**
     * @param string|ControllerAttribute $attr
     * @param string|null $entrypoint
     * @return mixed
     */
    public function getAttributeFor(ControllerAttribute|string $attr, ?string $entrypoint): mixed
    {
        if ($attr instanceof ControllerAttribute) {
            $attr = $attr->name;
        }

        return ($entrypoint ? ($this->attributes[$attr][$entrypoint] ?? null) : null)
            ?? $this->attributes[$attr]["__class"]
            ?? ($entrypoint ? ($this->attributes["__parent"][$attr][$entrypoint] ?? null) : null)
            ?? $this->attributes["__parent"][$attr]["__class"]
            ?? null;
    }

    /**
     * @param ControllerAttribute|string $attr
     * @param string|null $entrypoint
     * @return array
     */
    public function getAggregatedAttributeFor(
        ControllerAttribute|string $attr,
        ?string                    $entrypoint
    ): array
    {
        if ($attr instanceof ControllerAttribute) {
            $attr = $attr->name;
        }

        $nodes = [];
        $nodes[] = $entrypoint ? ($this->attributes[$attr][$entrypoint] ?? null) : null;
        $nodes[] = $this->attributes[$attr]["__class"] ?? null;
        if ($this->attributes["__parent"]) {
            $nodes[] = $entrypoint ? ($this->attributes["__parent"][$attr][$entrypoint] ?? null) : null;
            $nodes[] = $this->attributes["__parent"][$attr]["__class"] ?? null;
        }

        $nodes = array_filter($nodes, fn($node) => $node && is_array($node));
        if (!$nodes) {
            return [];
        }

        return array_merge_recursive(...$nodes);
    }
}