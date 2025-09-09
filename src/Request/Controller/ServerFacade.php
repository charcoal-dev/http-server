<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Controller;

use Charcoal\Http\Server\Config\VirtualHost;
use Charcoal\Http\TrustProxy\Result\TrustGatewayResult;

/**
 * Represents a facade for server operations, encapsulating a virtual host and trust gateway result.
 */
final readonly class ServerFacade
{
    public function __construct(
        public VirtualHost        $host,
        public TrustGatewayResult $proxy
    )
    {
    }
}