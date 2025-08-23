<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Controllers;

/**
 * Interface ContextAwareController
 * Defines the contract for a controller that is aware of a specific execution context.
 * This interface is designed to ensure controllers can handle context-specific data.
 */
interface ContextAwareControllerInterface extends ControllerInterface
{
    public function setContext(int $seqNo, ControllerContextInterface $context): void;
}