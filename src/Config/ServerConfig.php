<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Config;

use Charcoal\Http\Commons\Support\CorsPolicy;
use Charcoal\Http\TrustProxy\Config\TrustedProxy;

/**
 * Represents a server configuration that includes a list of HTTP servers
 * and trusted proxies, along with settings for TLS enforcement and
 * www alias handling.
 */
final readonly class ServerConfig
{
    /** @var VirtualHost[] */
    public array $hostnames;
    /** @var TrustedProxy[] */
    public array $proxies;

    /**
     * @param VirtualHost[] $hostnames
     * @param TrustedProxy[] $proxies
     */
    public function __construct(
        array                     $hostnames,
        array                     $proxies,
        public CorsPolicy         $corsPolicy,
        public RequestConstraints $requests,
        public bool               $enforceTls = true,
        public bool               $wwwSupport = true
    )
    {
        // Hostnames Setup
        $checked = [];
        foreach ($hostnames as $hostname) {
            if (!$hostname instanceof VirtualHost) {
                throw new \InvalidArgumentException("Required instance of: " . VirtualHost::class);
            }

            $indexId = $hostname->hostname;
            if ($hostname->wildcard) $indexId = "*." . $indexId;
            if ($hostname->isSecure) $indexId .= "_ssl";
            if (isset($checked[$indexId])) {
                throw new \InvalidArgumentException("Duplicate hostname: " . $indexId);
            }

            $checked[$indexId] = true;
        }

        $this->hostnames = $hostnames;

        // Trusted Proxies Setup
        $checked = [];
        foreach ($proxies as $proxy) {
            if (!$proxy instanceof TrustedProxy) {
                throw new \InvalidArgumentException("Required instance of: " . TrustedProxy::class);
            }

            $checksum = $proxy->checksum();
            if (isset($checked[$checksum])) {
                throw new \InvalidArgumentException("Duplicate proxy: " . bin2hex($checksum));
            }

            $checked[$checksum] = true;
        }

        $this->proxies = $proxies;
    }

    /**
     * @param string $hostname
     * @param int|null $port
     * @return VirtualHost|null
     */
    public function matchHostname(string $hostname, ?int $port): ?VirtualHost
    {
        if ($this->wwwSupport && str_starts_with($hostname, "www.")) {
            $hostname = substr($hostname, 4);
        }

        foreach ($this->hostnames as $profile) {
            if ($profile->matches($hostname, $port)) {
                return $profile;
            }
        }

        return null;
    }
}