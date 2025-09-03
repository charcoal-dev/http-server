<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

/**
 * Represents the content encoding types as defined in HTTP standards.
 * Provides predefined cases for identity encoding, which indicates no
 * transformation or compression has been applied.
 */
enum ContentEncoding: string
{
    case Identity = "identity";

    public static function find(?string $value): ?self
    {
        $value = trim(strtolower($value ?? ""));
        if (!$value) {
            return self::Identity;
        }

        return self::tryFrom(strtolower($value));
    }
}