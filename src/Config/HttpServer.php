<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Config;

use Charcoal\Base\Support\Helpers\NetworkHelper;

/**
 * Represents an HTTP server with a specified hostname and optional list of ports.
 * Ensures that the hostname is valid and that all provided ports are within the valid range.
 */
final readonly class HttpServer
{
    public string $hostname;
    public bool $wildcard;
    public bool $isIpAddress;
    public ?array $ports;

    public function __construct(string $hostname, public bool $enforceTls, int ...$ports)
    {
        $hostname = str_ends_with($hostname, ".") ? substr($hostname, 0, -1) : $hostname;
        $this->wildcard = str_starts_with($hostname, "*.");
        $this->isIpAddress = NetworkHelper::isValidIp($hostname);
        $this->hostname = strtolower($this->wildcard ? substr($hostname, 2) : $hostname);
        if ($this->wildcard) {
            if ($this->isIpAddress) {
                throw new \InvalidArgumentException("Cannot use wildcard with IP address: " . $hostname);
            }

            if (!str_contains($this->hostname, ".")) {
                throw new \InvalidArgumentException("Cannot use wildcard with non-TLD hostname: " . $hostname);
            }
        }

        if (!NetworkHelper::isValidHostname($this->hostname, allowIpAddr: true, allowNonTld: true)) {
            throw new \InvalidArgumentException("Invalid hostname: " . $hostname);
        }

        if ($ports) {
            foreach ($ports as $port) {
                if ($port < 1 || $port > 0xffff) {
                    throw new \OutOfRangeException("Invalid port: " . $port);
                }
            }

            $ports = array_values(array_unique($ports));
        }

        $this->ports = $ports ?: null;
    }

    /**
     * Checks if the given hostname and port match the stored configuration.
     * Expects the hostname to be lowercased and "www." prefix already removed.
     */
    public function matches(string $hostname, ?int $port): bool
    {
        if ($this->wildcard) {
            $hostname = explode(".", $hostname);
            unset($hostname[0]);
            $hostname = implode(".", $hostname);
        }

        if ($this->hostname !== $hostname) {
            return false;
        }

        return !$this->ports || in_array($port, $this->ports);
    }
}