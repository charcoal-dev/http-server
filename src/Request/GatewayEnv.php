<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

/**
 * Represents environment details for a gateway connection, including information about the
 * remote peer, host, port, and scheme of the current request.
 */
final readonly class GatewayEnv
{
    public ?string $peerIp;
    public ?string $hostname;
    public ?int $port;
    public bool $https;

    public function __construct(?string $peerIp = null, ?string $host = null, ?int $port = null, ?bool $https = null)
    {
        $this->peerIp = $peerIp ?? $_SERVER["REMOTE_ADDR"] ?? null;
        $this->hostname = $host ?? $_SERVER["HTTP_HOST"] ?? null;
        $this->port = $port ?? (isset($_SERVER["SERVER_PORT"]) ? intval($_SERVER["SERVER_PORT"]) : null);
        $this->https = $https ?: isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) === "on";
    }
}