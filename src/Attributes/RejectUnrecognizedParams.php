<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

/**
 * An attribute that denotes whether to reject unrecognized parameters for a method.
 * @property-read bool $enforce Indicates whether the enforcement is enabled or disabled. Defaults to true.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final readonly class RejectUnrecognizedParams
{
    public bool $enforce;

    public function __construct(bool $enforce = true)
    {
        $this->enforce = $enforce;
    }
}