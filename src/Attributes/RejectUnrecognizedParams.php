<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;

/**
 * An attribute that can be applied to a method to enforce the rejection
 * of unrecognized parameters during its invocation or processing.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class RejectUnrecognizedParams implements ControllerAttributeInterface
{
    public bool $enforce;

    public function __construct(bool $enforce = true)
    {
        $this->enforce = $enforce;
    }

    /**
     * @return \Closure
     */
    public function getBuilderFn(): \Closure
    {
        return fn(mixed $current, RejectUnrecognizedParams $attrInstance): bool => $attrInstance->enforce;
    }
}