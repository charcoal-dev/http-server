<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Config;

use Charcoal\Base\Support\Helpers\NetworkHelper;

/**
 * Immutable class representing a trusted proxy configuration.
 * This class is used to validate and manage a list of allowed CIDR blocks for trusted
 * proxies and determine whether the "Forwarded" header should be used.
 * @property-read bool $useForwarded Indicates whether the "Forwarded" header should be used.
 * @property-read array<string,array{string,string}> $allowedCidr Contains the validated list of allowed CIDR blocks.
 */
readonly class TrustedProxy
{
    /** @var array<string,array{string,string}> */
    private array $allowedCidr;

    public function __construct(
        public bool $useForwarded,
        array       $cidrList
    )
    {
        $cidrCount = count($cidrList);
        if ($cidrCount < 1) {
            throw new \InvalidArgumentException("At least one CIDR must be provided");
        }

        $this->allowedCidr = NetworkHelper::parseCidrListToBinary($cidrList);
        if (count($this->allowedCidr) !== $cidrCount) {
            throw new \InvalidArgumentException("One or more CIDR blocks are invalid");
        }
    }

    /**
     * @return string
     */
    public function checksum(): string
    {
        return md5(serialize($this->allowedCidr), true);
    }

    /**
     * Determines if the given binary IP address matches any network ranges in the allowed CIDR list.
     */
    public function match(string $ipBinary): bool
    {
        foreach ($this->allowedCidr as $network) {
            if (NetworkHelper::ipInCidrBinary($ipBinary, true, $network[0], $network[1])) {
                return true;
            }
        }

        return false;
    }
}