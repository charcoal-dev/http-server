<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Snapshot;

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

        return $entrypoint !== null
            ? ($this->attributes[$attr][$entrypoint]
                ?? $this->attributes[$attr]["__class"]
                ?? $this->attributes["__parent"][$attr][$entrypoint]
                ?? $this->attributes["__parent"][$attr]["__class"]
                ?? null)
            : ($this->attributes[$attr]["__class"]
                ?? $this->attributes["__parent"][$attr]["__class"]
                ?? null);
    }
}