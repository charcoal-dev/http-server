<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Controllers;

use Charcoal\Http\Server\Request\Controller\RequestFacade;

/**
 * Represents an interface for validation error enumerations.
 * Provides methods to retrieve translated error messages and error codes.
 */
interface ValidationErrorEnumInterface extends \UnitEnum
{
    public function getTranslatedMessage(RequestFacade $context): string;
}