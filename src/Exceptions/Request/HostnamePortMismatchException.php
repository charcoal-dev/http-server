<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Request;

use Charcoal\Contracts\Errors\ExceptionTraceContextInterface;
use Charcoal\Http\TrustProxy\Config\ServerEnv;
use Charcoal\Http\TrustProxy\Result\TrustGatewayResult;

/**
 * Exception thrown when a mismatch occurs between a hostname and a port.
 */
final class HostnamePortMismatchException extends \Exception implements ExceptionTraceContextInterface
{
    /**
     * Returns a new instance with trace context.
     */
    public static function withContext(ServerEnv $env, TrustGatewayResult $proxyResult): self
    {
        return new self([
            "peerIp" => $env->peerIp,
            "clientIp" => $proxyResult->clientIp,
            "hostname" => $proxyResult->hostname,
            "port" => $proxyResult->port,
            "scheme" => $proxyResult->scheme,
            "proxy" => [
                "hop" => $proxyResult->proxyHop,
                "matched" => $proxyResult->proxy
            ]
        ]);
    }

    /**
     * @param array $context
     */
    public function __construct(
        public readonly array $context = [],
    )
    {
        parent::__construct("Hostname and port did not match");
    }

    /**
     * @return array
     */
    public function getTraceContext(): array
    {
        return $this->context;
    }
}