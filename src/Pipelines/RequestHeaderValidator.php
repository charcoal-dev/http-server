<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Pipelines;

use Charcoal\Base\Enums\Charset;
use Charcoal\Http\Commons\Enums\HeaderKeyValidation;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Server\Contracts\Middleware\RequestHeadersPipeline;

/**
 * Validates and processes HTTP request headers according to defined constraints.
 * Implements the RequestHeadersPipeline contract, ensuring headers conform to specified validation rules
 * and do not exceed defined limits for count and size.
 */
final readonly class RequestHeaderValidator implements RequestHeadersPipeline
{
    public function execute(array $params): HeadersImmutable
    {
        return $this->__invoke(...$params);
    }

    /**
     * @see RequestHeadersPipeline
     */
    public function __invoke(
        HeadersImmutable    $headers,
        int                 $maxHeaders,
        int                 $maxHeaderLength,
        HeaderKeyValidation $keyValidation
    ): HeadersImmutable
    {
        if ($headers->count() > $maxHeaders) {
            throw new \OutOfRangeException("Headers exceed maximum length: " . $maxHeaders);
        }

        $normalized = new Headers();
        foreach ($headers->getArray() as $name => $value) {
            if (!$keyValidation->isValidName($name)) {
                throw new \InvalidArgumentException("Invalid header key received");
            }

            if (strlen($name) > 96) {
                throw new \OutOfRangeException("Header key exceeds maximum length: 96 bytes");
            } elseif (strlen($value) > $maxHeaderLength) {
                throw new \OutOfRangeException("Header value for " . $name . " exceeds maximum length: " .
                    $maxHeaderLength . " bytes");
            }

            if (!$keyValidation->isValidValue($value, Charset::ASCII)) {
                throw new \DomainException("Invalid header value received: " . $name);
            }

            $normalized->set($name, trim($value));
        }

        return $normalized->toImmutable();
    }
}