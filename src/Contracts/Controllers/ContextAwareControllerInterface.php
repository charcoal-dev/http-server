<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers;

/**
 * Interface ContextAwareController
 * Defines the contract for a controller that is aware of a specific execution context.
 * This interface is designed to ensure controllers can handle context-specific data.
 * Todo:???
 */
interface ContextAwareControllerInterface extends ControllerInterface
{
    public function setContext(int $seqNo, ControllerContextInterface $context): void;
}