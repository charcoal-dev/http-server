<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Attributes\Controllers;

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