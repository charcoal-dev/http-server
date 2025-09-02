<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Snapshot;

use Charcoal\Http\Server\Attributes\DefaultEntrypoint;
use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Server\Contracts\Controllers\InvokableControllerInterface;

/**
 * Represents the execution and validation context for a controller class.
 * This class is responsible for managing controller entry points, validating
 * the controller class, and storing associated attributes.
 */
final readonly class ControllerContext
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
            $this->defaultEntrypoint = $defaultEp;
            $entryPoints = [$this->defaultEntrypoint];
        }

        // Check all mentioned entry-points exist and accessible
        $epAttributes = [];
        foreach ($entryPoints as $entrypoint) {
            if (!$reflect->hasMethod($entrypoint)) {
                throw new \InvalidArgumentException("Controller entrypoint does not exist: " . $classname . "::" . $entrypoint);
            }

            $epMethod = $reflect->getMethod($entrypoint);
            if (!$epMethod->isPublic() || $epMethod->isStatic()) {
                throw new \InvalidArgumentException("Controller entrypoint must be public: " . $classname . "::" . $entrypoint);
            }

            $epAttributes[$entrypoint] = $epMethod->getAttributes();
        }

        if (!$entryPoints) {
            throw new \InvalidArgumentException("Controller must declare at least one entrypoint: " . $classname);
        }

        $this->entryPoints = $entryPoints;
        $this->attributes = new ControllerAttributes($reflect, $epAttributes);
        $this->validated = true;
    }
}