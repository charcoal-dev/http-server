<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Routing\Snapshot;

use Charcoal\Http\Server\Attributes\AllowedParam;
use Charcoal\Http\Server\Attributes\RejectUnrecognizedParams;
use Charcoal\Http\Server\Attributes\RequestConstraintOverride;

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
    public array $constraints;

    public function __construct(?\ReflectionClass $reflect)
    {
        if (!$reflect) {
            $this->allowedParams = [];
            $this->rejectUnrecognizedParams = true;
            return;
        }

        // Allowed list params
        $allowedParams = [];
        $allowedList = $reflect->getAttributes(AllowedParam::class);
        if ($allowedList) {
            foreach ($allowedList as $attrAllows) {
                $allowedParams = array_merge($allowedParams, $attrAllows->newInstance()->params);
            }
        }

        $this->allowedParams = $allowedParams;

        // Reject unrecognized params
        $rejectUnrecognizedParams = $reflect->getAttributes(RejectUnrecognizedParams::class);
        $this->rejectUnrecognizedParams = $rejectUnrecognizedParams ?
            $rejectUnrecognizedParams[0]->newInstance()->reject : true;

        # Request constraints overrides
        $constraintsOverrides = [];
        $attrConstraintsOverrides = $reflect->getAttributes(RequestConstraintOverride::class);
        if ($attrConstraintsOverrides) {
            foreach ($attrConstraintsOverrides as $attrConstraintsOverride) {
                $override = $attrConstraintsOverride->newInstance();
                $constraintsOverrides[$override->constraint->name] = $override->value;
            }
        }

        $this->constraints = $constraintsOverrides;
    }
}