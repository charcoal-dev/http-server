<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers\Hooks;

use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;

/**
 * Interface for classes that are aware of a "before entry point" callable.
 * This allows execution of a specific callable before reaching the application's main entry point.
 */
interface BeforeEntrypointAwareInterface extends ControllerInterface
{
    public function setBeforeEntrypoint(callable $beforeEntrypoint): void;
}