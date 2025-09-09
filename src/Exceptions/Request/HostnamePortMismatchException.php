<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Request;

use Charcoal\Contracts\Errors\ExceptionTraceContextInterface;
use Charcoal\Http\TrustProxy\Config\TrustedProxy;

/**
 * Exception thrown when a mismatch occurs between a hostname and a port.
 */
final class HostnamePortMismatchException extends \Exception implements ExceptionTraceContextInterface
{
    public function __construct(
        public readonly string        $peerIp,
        public readonly ?TrustedProxy $proxy,
        public readonly string        $scheme,
        public readonly string        $hostname,
        public readonly ?int          $port,
    )
    {
        parent::__construct("Hostname and port did not match");
    }

    /**
     * @return array
     */
    public function getTraceContext(): array
    {
        return [
            "peerIp" => $this->peerIp,
            "proxy" => $this->proxy,
            "scheme" => $this->scheme,
            "hostname" => $this->hostname,
            "port" => $this->port,
        ];
    }
}