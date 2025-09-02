<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Config;

use Charcoal\Http\Commons\Enums\HeaderKeyValidation;
use Charcoal\Http\Commons\Enums\ParamKeyValidation;
use Charcoal\Http\Server\Enums\RequestConstraint;

/**
 * Represents a set of constraints that can be applied to HTTP request handling.
 * Includes limitations on URI length, headers, body size, and parameter properties.
 * This class is designed to enforce constraints for request validation, avoiding
 * potential security or performance issues caused by overly large or complex inputs.
 */
final class RequestConstraints
{
    private int $maxBodyBytes;
    private int $maxParams;
    private int $maxParamLength;
    private int $dtoMaxDepth;

    public function __construct(
        public readonly int                 $maxUriBytes = 256,
        public readonly int                 $maxHeaders = 40,
        public readonly int                 $maxHeaderLength = 256,
        public readonly HeaderKeyValidation $headerKeyValidation = HeaderKeyValidation::RFC7230,
        public readonly ParamKeyValidation  $paramKeyValidation = ParamKeyValidation::STRICT,
        int                                 $maxBodyBytes = 10240,
        int                                 $maxParams = 32,
        int                                 $maxParamLength = 256,
        int                                 $dtoMaxDepth = 3,
    )
    {
        $this->maxBodyBytes = $maxBodyBytes;
        $this->maxParams = $maxParams;
        $this->maxParamLength = $maxParamLength;
        $this->dtoMaxDepth = $dtoMaxDepth;
    }

    /**
     * @deprecated
     */
    public function change(RequestConstraint $override, mixed $value): void
    {
        if (!is_int($value) || $value < 0 || $value > 0xFFFFFFFE) {
            throw new \InvalidArgumentException("Invalid HTTP constraint override value: " . $override->name);
        }

        $this->{$override->name} = $value;
    }

    /**
     * @param RequestConstraint $enum
     * @return int
     */
    public function get(RequestConstraint $enum): int
    {
        return $this->{$enum->name};
    }
}