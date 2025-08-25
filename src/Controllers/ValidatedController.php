<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Controllers;

use Charcoal\Http\Router\Attributes\Controllers\DefaultEntrypoint;
use Charcoal\Http\Router\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Router\Contracts\Controllers\InvokableControllerInterface;

/**
 * Represents a validated controller class designed to hold the configuration, entry points, and app context.
 * This class ensures the controller adheres to specific requirements such as implementing mandatory interfaces, being final,
 * and validating its declared entry points.
 */
final readonly class ValidatedController
{
    public array $entryPoints;
    public ?string $defaultEntrypoint;
    public ControllerAttributes $attributes;
    public bool $validated;

    public function __construct(
        public string $classname,
        array         $entryPoints,
        bool          $isTesting = false
    )
    {
        $entryPoints = array_unique($entryPoints);

        if ($isTesting) {
            $this->entryPoints = $entryPoints;
            $this->defaultEntrypoint = null;
            $this->attributes = new ControllerAttributes(null);
            $this->validated = false;
            return;
        }

        if (!class_exists($classname)) {
            throw new \InvalidArgumentException("Controller class does not exist: " . $classname);
        }

        // Reflection checks on controller class
        $reflect = new \ReflectionClass($classname);
        if (!$reflect->implementsInterface(ControllerInterface::class)) {
            throw new \InvalidArgumentException("Controller class must implement: " . ControllerInterface::class);
        }

        if (!$reflect->isInstantiable()) {
            throw new \InvalidArgumentException("Controller class must be instantiable: " . $classname);
        }

        if (!$reflect->isFinal()) {
            throw new \InvalidArgumentException("Controller class must be declared final: " . $classname);
        }

        // Default entrypoint declaration (if any)
        $defaultEp = $reflect->getAttributes(DefaultEntrypoint::class);
        $defaultEp = $defaultEp ? $defaultEp[0]->newInstance()->method : null;
        if ($reflect->implementsInterface(InvokableControllerInterface::class)) {
            if ($defaultEp) {
                throw new \InvalidArgumentException("Controller class cannot declare a default entrypoint when implementing " .
                    InvokableControllerInterface::class);
            }

            $defaultEp = "__invoke";
        }

        if ($defaultEp) {
            $entryPoints[] = $defaultEp;
        }

        // Check all mentioned entry-points exist and accessible
        foreach ($entryPoints as $entrypoint) {
            if (!$reflect->hasMethod($entrypoint)) {
                throw new \InvalidArgumentException("Controller entrypoint does not exist: " . $classname . "::" . $entrypoint);
            }

            $epMethod = $reflect->getMethod($entrypoint);
            if (!$epMethod->isPublic() || $epMethod->isStatic()) {
                throw new \InvalidArgumentException("Controller entrypoint must be public: " . $classname . "::" . $entrypoint);
            }
        }

        $this->attributes = new ControllerAttributes($reflect);
        $this->entryPoints = $entryPoints;
        $this->validated = true;
    }
}