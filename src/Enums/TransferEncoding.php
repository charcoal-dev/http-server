<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

/**
 * This enum defines the available transfer encoding options that can be applied
 * to HTTP messages. Transfer encoding is used to specify how the content of the
 * message body is formatted and transmitted between the client and server.
 */
enum TransferEncoding: string
{
    case Chunked = "chunked";

    public static function find(?string $value): ?self
    {
        if (!$value) {
            return null;
        }

        return self::tryFrom(strtolower($value));
    }
}