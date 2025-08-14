<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Support;

use Charcoal\Base\Enums\ValidationState;
use Charcoal\Http\Commons\Enums\HeaderKeyPolicy;
use Charcoal\Http\Commons\Enums\ParamKeyPolicy;
use Charcoal\Http\Router\Policy\HeadersPolicy;
use Charcoal\Http\Router\Policy\PayloadPolicy;

/**
 * Class PolicyHelper
 * @package Charcoal\Http\Router\Support
 */
class PolicyHelper
{
    public static function getRequestHeaderPolicy(): HeadersPolicy
    {
        return new HeadersPolicy(
            HeaderKeyPolicy::STRICT,
            keyMaxLength: 64,
            keyOverflowTrim: true,
            valueMaxLength: 2048,
            valueOverflowTrim: true,
            accessKeyTrust: ValidationState::VALIDATED,
            setterKeyTrust: ValidationState::RAW,
            valueTrust: ValidationState::RAW,
        );
    }

    public static function getResponseHeaderPolicy(): HeadersPolicy
    {
        return new HeadersPolicy(
            HeaderKeyPolicy::STRICT,
            keyMaxLength: 64,
            keyOverflowTrim: true,
            valueMaxLength: 2048,
            valueOverflowTrim: true,
            accessKeyTrust: ValidationState::VALIDATED,
            setterKeyTrust: ValidationState::RAW,
            valueTrust: ValidationState::VALIDATED,
        );
    }

    public static function getRequestPayloadPolicy(int $maxParamLength = 2048): PayloadPolicy
    {
        return new PayloadPolicy(
            ParamKeyPolicy::STRICT,
            keyMaxLength: 64,
            keyOverflowTrim: true,
            valueMaxLength: $maxParamLength,
            valueOverflowTrim: true,
            accessKeyTrust: ValidationState::VALIDATED,
            setterKeyTrust: ValidationState::RAW,
            valueTrust: ValidationState::RAW,
        );
    }

    public static function getResponsePayloadPolicy(int $maxParamLength = 2048): PayloadPolicy
    {
        return new PayloadPolicy(
            ParamKeyPolicy::STRICT,
            keyMaxLength: 64,
            keyOverflowTrim: true,
            valueMaxLength: $maxParamLength,
            valueOverflowTrim: true,
            accessKeyTrust: ValidationState::VALIDATED,
            setterKeyTrust: ValidationState::VALIDATED,
            valueTrust: ValidationState::TRUSTED,
        );
    }
}