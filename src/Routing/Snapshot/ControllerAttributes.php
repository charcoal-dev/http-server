<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Snapshot;

use Charcoal\Http\Server\Attributes\AllowedParam;
use Charcoal\Http\Server\Attributes\RejectUnrecognizedParams;
use Charcoal\Http\Server\Attributes\RequestConstraintOverride;

/**
 * Represents metadata and configuration attributes for a controller.
 * This class provides functionality for handling allowed parameters
 * and rejecting unrecognized parameters based on reflection data
 * retrieved from controller attributes.
 */
final readonly class ControllerAttributes
{
    public array $allowedParams;
    public array $rejectUnrecognizedParams;
    public array $constraints;

    /**
     * @param \ReflectionClass|null $reflect
     * @param array<string, \ReflectionMethod> $methods
     */
    public function __construct(?\ReflectionClass $reflect, array $methods = [])
    {
        if (!$reflect) {
            $this->allowedParams = [];
            $this->rejectUnrecognizedParams = [["__class" => true]];
            $this->constraints = [];
            return;
        }

        // Allowed list params
        $this->allowedParams = $this->readClassMethodAttributes($reflect, $methods,
            AllowedParam::class, true,
            fn(mixed $current, AllowedParam $attrInstance): array => array_merge($current, $attrInstance->params));

        $this->rejectUnrecognizedParams = $this->readClassMethodAttributes($reflect, $methods,
            RejectUnrecognizedParams::class, false,
            fn(mixed $current, RejectUnrecognizedParams $attrInstance): bool => $attrInstance->enforce
        );

        // Request constraints overrides
        $this->constraints = $this->readClassMethodAttributes($reflect, [],
            RequestConstraintOverride::class, true,
            function (array $current, RequestConstraintOverride $attrInstance): array {
                $current[$attrInstance->constraint->name] = $attrInstance->value;
                return $current;
            }
        );
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