<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Snapshot;

use Charcoal\Http\Server\Attributes\AllowedParam;
use Charcoal\Http\Server\Attributes\AllowFileUpload;
use Charcoal\Http\Server\Attributes\DisableRequestBody;
use Charcoal\Http\Server\Attributes\RejectUnrecognizedParams;
use Charcoal\Http\Server\Attributes\RequestConstraintOverride;
use Charcoal\Http\Server\Enums\ControllerAttribute;

/**
 * Represents metadata and configuration attributes for a controller.
 * This class provides functionality for handling allowed parameters
 * and rejecting unrecognized parameters based on reflection data
 * retrieved from controller attributes.
 */
final readonly class ControllerAttributes
{
    public array $map;

    /**
     * @param \ReflectionClass|null $reflect
     * @param array<string, \ReflectionMethod> $methods
     */
    public function __construct(?\ReflectionClass $reflect, array $methods = [])
    {
        $map = [];
        if (!$reflect) {
            $this->map = $map;
            return;
        }

        // Allowed list params
        $map[ControllerAttribute::allowedParams->name] = $this->readClassMethodAttributes($reflect, $methods,
            AllowedParam::class, true,
            fn(mixed $current, AllowedParam $attrInstance): array => array_merge($current, $attrInstance->params));

        $map[ControllerAttribute::rejectUnrecognizedParams->name] = $this->readClassMethodAttributes($reflect, $methods,
            RejectUnrecognizedParams::class, false,
            fn(mixed $current, RejectUnrecognizedParams $attrInstance): bool => $attrInstance->enforce
        );

        // Request constraints overrides
        $map[ControllerAttribute::constraints->name] = $this->readClassMethodAttributes($reflect, [],
            RequestConstraintOverride::class, true,
            function (array $current, RequestConstraintOverride $attrInstance): array {
                $current[$attrInstance->constraint->name] = $attrInstance->value;
                return $current;
            }
        );

        // Disable request body
        $map[ControllerAttribute::disableRequestBody->name] = $this->readClassMethodAttributes($reflect, [],
            DisableRequestBody::class, false,
            fn(mixed $current, DisableRequestBody $attrInstance): bool => true
        );

        // Allow File Uploads?
        $map[ControllerAttribute::allowFileUpload->name] = $this->readClassMethodAttributes($reflect, [],
            AllowFileUpload::class, false,
            fn(mixed $current, AllowFileUpload $attrInstance): array => ["size" => $attrInstance->maxFileSize]
        );

        $this->map = $map;
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
            ? ($this->map[$attr][$entrypoint] ?? $this->map[$attr]["__class"] ?? null)
            : ($this->map[$attr]["__class"] ?? null);
    }

    /**
     * @param \ReflectionClass $reflect
     * @param array $methods
     * @param string $attrClass
     * @param bool $repeats
     * @param \Closure $apply
     * @return array
     */
    private function readClassMethodAttributes(
        \ReflectionClass $reflect,
        array            $methods,
        string           $attrClass,
        bool             $repeats,
        \Closure         $apply
    ): array
    {
        $attributes = [];

        // On Class
        $onClass = $reflect->getAttributes($attrClass);
        if ($onClass) {
            $attributes["__class"] = $repeats ? [] : null;
            foreach ($onClass as $classAttr) {
                $attributes["__class"] = $apply($attributes["__class"], $classAttr->newInstance());
            }
        }

        // On Methods
        foreach ($methods as $name => $reflectM) {
            $onMethod = $reflectM->getAttributes($attrClass);
            if ($onMethod) {
                $attributes[$name] = $repeats ? [] : null;
                foreach ($onMethod as $methodAttr) {
                    $attributes[$name] = $apply($attributes[$name], $methodAttr->newInstance());
                }
            }
        }

        return $attributes;
    }
}