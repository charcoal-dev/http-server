<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Request;

use Charcoal\Http\Commons\Headers\Headers;

/**
 * Represents an interface for a success response.
 * Defines a contract for sending a response indicating successful processing.
 */
interface SuccessResponseInterface
{
    public function isCacheable(): bool;

    public function getStatusCode(): int;

    public function setHeaders(Headers $headers): void;

    public function send(): never;
}