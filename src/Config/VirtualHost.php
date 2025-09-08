<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Config;

use Charcoal\Http\Server\Enums\ForwardingMode;
use Charcoal\Net\Dns\HostnameHelper;
use Charcoal\Net\Ip\IpHelper;

/**
 * Represents a virtual host configuration with support for wildcard hostnames,
 * IP addresses, and optional port restrictions.
 */
final readonly class VirtualHost
{
    public string $hostname;
    public bool $wildcard;
    public bool $isIpAddress;

    public function __construct(
        string                $hostname,
        public int            $port,
        public bool           $isSecure,
        public ForwardingMode $forwarding,
        public bool           $allowInternal = false
    )
    {
        $hostname = str_ends_with($hostname, ".") ? substr($hostname, 0, -1) : $hostname;
        $this->wildcard = str_starts_with($hostname, "*.");
        $this->isIpAddress = (bool)IpHelper::isValidIp($hostname);
        $this->hostname = strtolower($this->wildcard ? substr($hostname, 2) : $hostname);
        if ($this->wildcard) {
            if ($this->isIpAddress) {
                throw new \InvalidArgumentException("Cannot use wildcard with IP address: " . $hostname);
            }

            if (!str_contains($this->hostname, ".")) {
                throw new \InvalidArgumentException("Cannot use wildcard with non-TLD hostname: " . $hostname);
            }
        }

        if (!$this->isIpAddress) {
            if (!HostnameHelper::isValidHostname($this->hostname, allowIpAddr: false, allowNonTld: true)) {
                throw new \InvalidArgumentException("Invalid hostname: " . $hostname);
            }
        }

        if ($this->port < 1 || $this->port > 0xffff) {
            throw new \OutOfRangeException("Invalid port: " . $port);
        }
    }

    /**
     * Checks if the given hostname and port match the stored configuration.
     * Expects the hostname to be lowercased and "www." prefix already removed.
     */
    public function matches(string $hostname, int $port): bool
    {
        if ($this->port !== $port) {
            return false;
        }

        if ($this->wildcard) {
            $hostname = explode(".", $hostname);
            unset($hostname[0]);
            $hostname = implode(".", $hostname);
        }

        if ($this->hostname !== $hostname) {
            return false;
        }

        return true;
    }
}