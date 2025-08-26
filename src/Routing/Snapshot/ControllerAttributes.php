<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Snapshot;

use Charcoal\Http\Server\Attributes\AllowedParam;
use Charcoal\Http\Server\Attributes\RejectUnrecognizedParams;

/**
 * Represents metadata and configuration attributes for a controller.
 * This class provides functionality for handling allowed parameters
 * and rejecting unrecognized parameters based on reflection data
 * retrieved from controller attributes.
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