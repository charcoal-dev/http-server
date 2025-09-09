<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Builder;

use Charcoal\Base\Arrays\ArrayHelper;
use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;
use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Server\Contracts\Controllers\InvokableControllerInterface;
use Charcoal\Http\Server\Enums\ControllerAttribute;
use Charcoal\Http\Server\HttpServer;
use Charcoal\Http\Server\Routing\Snapshot\ControllerAttributes;

/**
 * This class provides methods to set and retrieve cached controller entries, ensuring
 * no duplicate entries are added and throwing exceptions for invalid operations.
 */
final class ControllersBuildCache
{
    use NotSerializableTrait;
    use NotCloneableTrait;

    /** @var array<class-string<ControllerInterface>, ControllerAttributes> */
    private array $cache = [];
    /** @var array<class-string<ControllerInterface>, \ReflectionClass> */
    private array $reflectionClassCache = [];
    /** @var array<class-string<ControllerInterface>> */
    private array $abstracts = [];

    private bool $completed = false;

    /**
     * @param class-string<ControllerInterface> $fqcn
     * @param ControllerAttributes $reflection
     */
    public function set(string $fqcn, ControllerAttributes $reflection): void
    {
        if ($this->completed) {
            throw new \BadMethodCallException("ControllersBuildCache is already completed");
        }

        if (isset($this->cache[$fqcn])) {
            throw new \RuntimeException("Duplicate controller entry for: " . $fqcn);
        }

        $this->cache[$fqcn] = $reflection;
    }

    /**
     * @param class-string<ControllerInterface> $fqcn
     * @param null|array $entryPoints
     * @return ControllerAttributes
     */
    public function resolve(string $fqcn, ?array $entryPoints): ControllerAttributes
    {
        if ($this->completed) {
            throw new \BadMethodCallException("ControllersBuildCache is already completed");
        }

        $existing = $this->cache[$fqcn] ?? null;
        if (!$existing) {
            $entryPoints = is_array($entryPoints) ? array_unique($entryPoints) : null;
            if (!HttpServer::$validateControllerClasses) {
                $this->set($fqcn, new ControllerAttributes($fqcn, null, $entryPoints ?? [], [], false));
                return $this->cache[$fqcn];
            }

            $reflection = $this->getReflectionClass($fqcn);
            $isAbstract = $reflection->isAbstract();

            // Internal logic:
            // - This method calls itself with $entryPoints=null while checking for parents
            // - Abstract classes can definitely define entryPoints
            // - Route declarations cannot be defined on abstract classes
            if ($isAbstract && $entryPoints) {
                throw new \InvalidArgumentException("Controller class must be abstract: " . $fqcn);
            }

            if (!$isAbstract) {
                if (!$reflection->isInstantiable()) {
                    throw new \InvalidArgumentException("Controller class must be instantiable: " . $fqcn);
                }

                if (!$reflection->isFinal()) {
                    throw new \InvalidArgumentException("Controller class must be declared final: " . $fqcn);
                }
            }

            // Attributes Bag
            $attributes = [];
            $epReflections = [];

            // Resolve Inherited Chain
            [$chain, $inherited] = $this->aggregateInheritedAttributes($fqcn);
            $hasParent = $chain > 0;
            $attributes["__parent"] = $hasParent ? $inherited : null;
            $defaultEpBase = $this->readControllerAttributes(
                $reflection,
                [],
                ControllerAttribute::defaultEntrypoint,
                $attributes
            )["__class"] ?? null;

            if ($defaultEpBase) {
                $attributes[ControllerAttribute::defaultEntrypoint->name] = $defaultEpBase;
            }

            // [SanityCheck]: Ensure top level class implement ControllerInterface
            if (!$hasParent && !$reflection->implementsInterface(ControllerInterface::class)) {
                throw new \InvalidArgumentException(
                    "Controller class does not implement ControllerInterface: " . $fqcn);
            }

            // Has Default Entrypoint?
            $defaultEp = $attributes[ControllerAttribute::defaultEntrypoint->name]
                ?? $attributes["__parent"][ControllerAttribute::defaultEntrypoint->name]
                ?? null;

            if ($reflection->implementsInterface(InvokableControllerInterface::class)) {
                if ($defaultEp) {
                    throw new \InvalidArgumentException(
                        "Controller class cannot declare a default entrypoint when implementing " .
                        InvokableControllerInterface::class);
                }

                $defaultEp = "__invoke";
            }

            // No other entryPoints if default entrypoint is set:
            if ($defaultEp) {
                $entryPoints = [$defaultEp];
            }

            if (!$isAbstract) {
                // Has at least one entrypoint?
                if (!$entryPoints) {
                    throw new \InvalidArgumentException("Controller must declare at least one entrypoint: " . $fqcn);
                }

                // Validate that all mentioned entry-points exist and are accessible
                foreach ($entryPoints as $entrypoint) {
                    if (!$reflection->hasMethod($entrypoint)) {
                        throw new \InvalidArgumentException(
                            "Controller entrypoint does not exist: " . $fqcn . "::" . $entrypoint);
                    }

                    $epReflection = $reflection->getMethod($entrypoint);
                    if (!$epReflection->isPublic() || $epReflection->isStatic()) {
                        throw new \InvalidArgumentException(
                            "Controller entrypoint must be public: " . $fqcn . "::" . $entrypoint);
                    }

                    $epReflections[$entrypoint] = $epReflection;
                }
            }

            // Read all registered/known Attributes
            foreach (ControllerAttribute::cases() as $attr) {
                if ($attr === ControllerAttribute::defaultEntrypoint) {
                    continue;
                }

                $attrDeclared = $this->readControllerAttributes(
                    $reflection,
                    $epReflections,
                    $attr,
                    $attributes
                );

                if ($attrDeclared) {
                    $attributes[$attr->name] = $attrDeclared;
                }
            }

            // Sanity Checks
            if (isset($attributes[ControllerAttribute::disableRequestBody->name])) {
                $bodyDisabledIn = array_keys($attributes[ControllerAttribute::disableRequestBody->name]);
                foreach ([ControllerAttribute::enableRequestBody,
                             ControllerAttribute::allowFileUpload,
                             ControllerAttribute::allowTextBody] as $enablingAttr) {
                    $enabledBodyIn = array_keys($attributes[$enablingAttr->name] ?? []);
                    $intersect = array_intersect($bodyDisabledIn, $enabledBodyIn);
                    if ($intersect) {
                        throw new \InvalidArgumentException(
                            "Controller attribute " . $enablingAttr->name . " cannot be used with " .
                            ControllerAttribute::disableRequestBody->name . " in: " . $fqcn . "::" .
                            implode(", ", $intersect));
                    }
                }
            }

            if ($isAbstract) {
                $this->abstracts[] = $fqcn;
            }

            $existing = new ControllerAttributes(
                $fqcn,
                $defaultEp ?: null,
                $defaultEp ? null : $entryPoints,
                $attributes,
                true,
            );

            $this->set($fqcn, $existing);
            return $this->get($fqcn);
        }

        return $existing;
    }

    /**
     * @param string $fqcn
     * @return ControllerAttributes
     */
    public function get(string $fqcn): ControllerAttributes
    {
        return $this->cache[$fqcn] ?? throw new \RuntimeException("Controller not found: " . $fqcn);
    }

    /**
     * @return $this
     */
    public function complete(): self
    {
        unset($this->reflectionClassCache);
        $this->reflectionClassCache = [];
        return $this;
    }

    /**
     * @return array
     * @api
     */
    public function inspect(): array
    {
        return [
            "controllers" => $this->cache,
            "abstracts" => $this->abstracts,
        ];
    }

    /**
     * @param class-string<ControllerInterface> $fqcn
     * @return array<int, array>
     */
    private function aggregateInheritedAttributes(string $fqcn): array
    {
        $reflection = $this->getReflectionClass($fqcn);

        $chain = 0;
        $inherited = [];
        while (true) {
            $reflection = $reflection->getParentClass();
            if (!$reflection) {
                break;
            }

            $chain++;
            $attributes = $this->resolve($reflection->getName(), null);
            $parentAttributes = $attributes->attributes;
            unset($parentAttributes["__parent"]);
            $inherited = ArrayHelper::mergeAssocDeep($parentAttributes, $inherited);
            unset($attributes, $parentAttributes);
        }


        return [$chain, $inherited];
    }

    /**
     * @param \ReflectionClass $reflect
     * @param array<non-empty-string,\ReflectionMethod> $methods
     * @param ControllerAttribute $attr
     * @param array $current
     * @return array
     */
    private function readControllerAttributes(
        \ReflectionClass    $reflect,
        array               $methods,
        ControllerAttribute $attr,
        array               $current,
    ): array
    {
        $attribute = [];

        // Read Attribute meta
        $metaReflect = $this->getReflectionClass($attr->value)->getAttributes(\Attribute::class)[0] ?? null;
        $metaReflectArgs = $metaReflect?->getArguments() ?? null;
        $flags = $metaReflectArgs ? (int)($metaReflectArgs["flags"] ?? ($metaReflectArgs[0] ?? 0)) : 0;
        $isRepeatable = (bool)($flags & \Attribute::IS_REPEATABLE);

        // On Class
        $onClass = $reflect->getAttributes($attr->value);
        if ($onClass) {
            $attribute["__class"] = $current["__parent"][$attr->name]["__class"] ?? null;
            if (is_null($attribute["__class"]) && $isRepeatable) {
                $attribute["__class"] = [];
            }

            foreach ($onClass as $classAttr) {
                $classAttr = $classAttr->newInstance();
                /** @var ControllerAttributeInterface $classAttr */
                $attribute["__class"] = $classAttr->getBuilderFn()($attribute["__class"], $classAttr);
            }
        }

        // On Methods
        foreach ($methods as $name => $reflectM) {
            $onMethod = $reflectM->getAttributes($attr->value);
            if ($onMethod) {
                $attribute[$name] = $current["__parent"][$attr->name][$name]
                    ?? $current["__parent"][$attr->name]["__class"]
                    ?? null;
                if (is_null($attribute[$name]) && $isRepeatable) {
                    $attribute[$name] = [];
                }

                foreach ($onMethod as $methodAttr) {
                    $methodAttr = $methodAttr->newInstance();
                    /** @var ControllerAttributeInterface $classAttr */
                    $attribute[$name] = $methodAttr->getBuilderFn()($attribute[$name], $methodAttr);
                }
            }
        }

        return $attribute;
    }

    /**
     * @param string $fqcn
     * @return \ReflectionClass
     */
    private function getReflectionClass(string $fqcn): \ReflectionClass
    {
        if (!isset($this->reflectionClassCache[$fqcn])) {
            try {
                $this->reflectionClassCache[$fqcn] = new \ReflectionClass($fqcn);
            } catch (\ReflectionException $e) {
                throw new \InvalidArgumentException("Controller reflection instantiate error: " . $fqcn,
                    previous: $e);
            }
        }

        return $this->reflectionClassCache[$fqcn];
    }
}