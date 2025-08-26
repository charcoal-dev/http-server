<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers;

/**
 * Interface ControllerAttributeInterface
 * @package Charcoal\Http\Server\Contracts\Controllers
 */
interface ControllerAttributeInterface
{
    /**
     * @return \UnitEnum
     */
    public function namespace(): \UnitEnum;

    /**
     * @return string[]
     */
    public function properties(): array;
}