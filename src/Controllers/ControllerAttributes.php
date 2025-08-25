<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Controllers;

use Charcoal\Http\Router\Attributes\Controllers\RejectUnrecognizedParams;
use Charcoal\Http\Router\Attributes\Controllers\RequiredParams;

/**
 * Represents a controller's attributes.
 */
final readonly class ControllerAttributes
{
    public array $requiredParams;
    public bool $rejectUnrecognizedParams;

    public function __construct(?\ReflectionClass $reflect)
    {
        if (!$reflect) {
            $this->requiredParams = [];
            $this->rejectUnrecognizedParams = true;
            return;
        }

        // Required params
        $requiredParams = $reflect->getAttributes(RequiredParams::class);
        $this->requiredParams = $requiredParams ? $requiredParams[0]->newInstance()->params : [];

        // Reject unrecognized params
        $rejectUnrecognizedParams = $reflect->getAttributes(RejectUnrecognizedParams::class);
        $this->rejectUnrecognizedParams = $rejectUnrecognizedParams ?
            $rejectUnrecognizedParams[0]->newInstance()->reject : true;
    }
}