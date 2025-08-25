<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Config;

/**
 * Represents the configuration containing hostnames and trusted proxies.
 * This class enforces that all hostnames are instances of HttpServer,
 * and all proxies are instances of TrustedProxy. Duplicates are not allowed.
 */
final readonly class RouterConfig
{
    /** @var HttpServer[] */
    public array $hostnames;
    /** @var TrustedProxy[] */
    public array $proxies;

    /**
     * @param HttpServer[] $hostnames
     * @param TrustedProxy[] $proxies
     */
    public function __construct(
        array       $hostnames,
        array       $proxies,
        public bool $enforceTls = true,
        public bool $wwwAlias = true
    )
    {
        // Hostnames Setup
        $checked = [];
        foreach ($hostnames as $hostname) {
            if (!$hostname instanceof HttpServer) {
                throw new \InvalidArgumentException("Required instance of: " . HttpServer::class);
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