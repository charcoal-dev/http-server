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
use Charcoal\Http\Server\Attributes\DefaultEntrypoint;
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
        if($this->completed) {
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
        if($this->completed) {
            throw new \BadMethodCallException("ControllersBuildCache is already completed");
        }

        $existing = $this->cache[$fqcn] ?? null;
        if (!$existing) {
            $entryPoints = is_array($entryPoints) ? array_unique($entryPoints) : null;
            if (!HttpServer::$validateControllerClasses) {
                $this->set($fqcn, new ControllerAttributes($entryPoints, [], false));
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

            // Default Entrypoint
            $attributes[ControllerAttribute::defaultEntrypoint->name] = $this->readControllerAttributes(
                $reflection,
                [],
                DefaultEntrypoint::class
            );

            // Override Class Default Entrypoint, (bump [__class] as the primary value)
            if (isset($attributes[ControllerAttribute::defaultEntrypoint->name])) {
                $attributes[ControllerAttribute::defaultEntrypoint->name] =
                    $attributes[ControllerAttribute::defaultEntrypoint->name]["__class"];
            }

            // Resolve Inherited Chain
            [$chain, $inherited] = $this->aggregateInheritedAttributes($reflection);
            $hasParent = $chain > 0;
            $attributes["__parent"] = $inherited ?: null;

            // Interface check:
            // Ensures top level class implement ControllerInterface
            if (!$hasParent && !$reflection->implementsInterface(ControllerInterface::class)) {
                throw new \InvalidArgumentException(
                    "Controller class does not implement ControllerInterface: " . $fqcn);
            }

            // Has Default Entrypoint?
            $defaultEp = $attributes[ControllerAttribute::defaultEntrypoint->name] ?? null;
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

            // Read all registered Attributes
            foreach (ControllerAttribute::cases() as $attr) {
                if ($attr === ControllerAttribute::defaultEntrypoint) {
                    continue; // Skip
                }

                $attributes[$attr->name] = $this->readControllerAttributes(
                    $reflection,
                    $epReflections,
                    $attr->value
                );
            }

            if ($isAbstract) {
                $this->abstracts[] = $fqcn;
            }

            $existing = new ControllerAttributes(
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
     */
    public function inspect(): array
    {
        return [
            "controllers" => $this->cache,
            "abstracts" => $this->abstracts,
        ];
    }

    /**
     * @param \ReflectionClass $reflection
     * @return array
     */
    private function aggregateInheritedAttributes(\ReflectionClass $reflection): array
    {
        $chain = 0;
        $inherited = [];
        while (true) {
            $parent = $reflection->getParentClass();
            if (!$parent) {
                break;
            }

            $chain++;
            $parent = $this->resolve($parent->getName(), null);
            $inherited = ArrayHelper::mergeAssocDeep($inherited, $parent->attributes);
        }

        return [$chain, $inherited];
    }

    /**
     * @param \ReflectionClass $reflect
     * @param array<non-empty-string,\ReflectionMethod> $methods
     * @param class-string<ControllerAttributeInterface> $attrClass
     * @return array
     */
    private function readControllerAttributes(
        \ReflectionClass $reflect,
        array            $methods,
        string           $attrClass
    ): array
    {
        $attribute = [];

        // On Class
        $onClass = $reflect->getAttributes($attrClass);
        $flags = $onClass[0]->getArguments()[0] ?? null;
        $isRepeatable = (bool)(($flags ?? 0) & \Attribute::IS_REPEATABLE);

        if ($onClass) {
            $attribute["__class"] = $isRepeatable ? [] : null;
            foreach ($onClass as $classAttr) {
                $classAttr = $classAttr->newInstance();
                /** @var ControllerAttributeInterface $classAttr */
                $attribute["__class"] = $classAttr->getBuilderFn()($attribute["__class"], $classAttr);
            }
        }

        // On Methods
        foreach ($methods as $name => $reflectM) {
            $onMethod = $reflectM->getAttributes($attrClass);
            if (is_null($flags)) {
                $flags = $onMethod[0]->getArguments()[0] ?? 0;
                $isRepeatable = (bool)($flags & \Attribute::IS_REPEATABLE);
            }

            if ($onMethod) {
                $attribute[$name] = $isRepeatable ? [] : null;
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