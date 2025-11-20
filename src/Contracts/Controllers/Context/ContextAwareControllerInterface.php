<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers\Context;

use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;

/**
 * Interface ContextAwareController
 * Defines the contract for a controller that is aware of a specific execution context.
 * This interface is designed to ensure controllers can handle context-specific data.
 */
interface ContextAwareControllerInterface extends ControllerInterface
{
    /**
     * Called internally to pass-on the context objects to controllers.
     */
    public function setContext(ControllerContextInterface $context): void;

    /**
     * Called internally before any controller code execution to
     * validate that all the required context objects were received.
     */
    public function validateContext(): void;
}