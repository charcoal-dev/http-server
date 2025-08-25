<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Controllers;

use Charcoal\Http\Router\Controllers\ControllerContext;

/**
 * Represents an interface for validation error enumerations.
 * Provides methods to retrieve translated error messages and error codes.
 */
interface ValidationErrorEnumInterface extends \UnitEnum
{
    public function getTranslatedMessage(ControllerContext $context): string;
}