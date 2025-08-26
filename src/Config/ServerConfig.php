<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Config;

use Charcoal\Http\Server\TrustProxy\TrustedProxy;

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
        public RequestConstraints $requests,
        public bool               $enforceTls = true,
        public bool               $wwwAlias = true
    )
    {
        // Hostnames Setup
        $checked = [];
        foreach ($hostnames as $hostname) {
            if (!$hostname instanceof VirtualHost) {
                throw new \InvalidArgumentException("Required instance of: " . VirtualHost::class);
            }

            $hostname = $hostname->wildcard ? "*." . $hostname->hostname : $hostname->hostname;
            if (isset($checked[$hostname])) {
                throw new \InvalidArgumentException("Duplicate hostname: " . $hostname);
            }

            $checked[$hostname] = true;
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
}