<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Controllers;

use Charcoal\Http\Router\Attributes\Controllers\RejectUnrecognizedParams;
use Charcoal\Http\Router\Attributes\Controllers\AllowedParam;

/**
 * Represents a controller's attributes.
 */
final readonly class ControllerAttributes
{
    public array $allowedParams;
    public bool $rejectUnrecognizedParams;

    public function __construct(?\ReflectionClass $reflect)
    {
        if (!$reflect) {
            $this->allowedParams = [];
            $this->rejectUnrecognizedParams = true;
            return;
        }

        // Required params
        $requiredParams = $reflect->getAttributes(AllowedParam::class);
        $this->allowedParams = $requiredParams ? $requiredParams[0]->newInstance()->params : [];

        // Reject unrecognized params
        $rejectUnrecognizedParams = $reflect->getAttributes(RejectUnrecognizedParams::class);
        $this->rejectUnrecognizedParams = $rejectUnrecognizedParams ?
            $rejectUnrecognizedParams[0]->newInstance()->reject : true;
    }
}