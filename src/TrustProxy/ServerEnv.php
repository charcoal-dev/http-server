<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\TrustProxy;

/**
 * Represents the HTTP request header information,
 * including peer IP address, hostname, port, and HTTPS status.
 */
final readonly class ServerEnv
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