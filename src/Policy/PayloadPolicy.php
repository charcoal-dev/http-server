<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Policy;

use Charcoal\Base\Enums\Charset;
use Charcoal\Base\Enums\ValidationState;
use Charcoal\Http\Commons\Data\HttpDataPolicy;
use Charcoal\Http\Commons\Enums\HttpParamKeyPolicy;

/**
 * Class HttpPayloadPolicy
 * @package Charcoal\Http\Router\Policy
 */
readonly class PayloadPolicy extends HttpDataPolicy
{
    public function __construct(
        public HttpParamKeyPolicy         $keyPolicy = HttpParamKeyPolicy::STRICT,
        Charset                           $charset = Charset::UTF8,
        int                               $keyMaxLength = 64,
        bool                              $keyOverflowTrim = false,
        int                               $valueMaxLength = 2048,
        bool                              $valueOverflowTrim = false,
        ValidationState                   $accessKeyTrust = ValidationState::VALIDATED,
        ValidationState                   $setterKeyTrust = ValidationState::RAW,
        ValidationState                   $valueTrust = ValidationState::RAW,
    )
    {
        parent::__construct(
            charset: $charset,
            keyMaxLength: $keyMaxLength,
            keyOverflowTrim: $keyOverflowTrim,
            valueMaxLength: $valueMaxLength,
            valueOverflowTrim: $valueOverflowTrim,
            accessKeyTrust: $accessKeyTrust,
            setterKeyTrust: $setterKeyTrust,
            valueTrust: $valueTrust,
        );
    }
}