<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Request;

/**
 * Exception thrown when a mismatch occurs between a hostname and a port.
 */
final class HostnamePortMismatchException extends \Exception
{
    public function __construct(
        public readonly string $hostname,
        public readonly ?int   $port
    )
    {
        parent::__construct("Hostname and port did not match");
    }
}