<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Router;

use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Enums\HttpProtocol;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Router\Config\HttpServer;
use Charcoal\Http\Router\Config\RouterConfig;
use Charcoal\Http\Router\Config\TrustedProxy;
use Charcoal\Http\Router\Exceptions\RequestContextException;
use Charcoal\Http\Router\Request\GatewayEnv;
use Charcoal\Http\Router\Request\ServerRequest;
use Charcoal\Http\Router\Request\TrustGateway;
use PHPUnit\Framework\TestCase;

/**
 * Class GatewayTest
 * @package Charcoal\Http\Tests\Router
 */
final class GatewayTest extends TestCase
{
    /**
     * @throws RequestContextException
     */
    public function testGateway_Xff_LongChain_Index7_UsesNearestTrustedAndCustomPort(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 6001)],
            [new TrustedProxy(true, ["10.0.0.0/8"], 10)],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.0.0.99"; // nearest trusted proxy (must match allowed CIDR)
        $headers = new Headers();

        // XFF: left→right = client→...→nearest; we walk right→left.
        // We want the first non-trusted at reversed index 7.
        $headers->set("X-Forwarded-For", "garbage, unknown, 203.0.113.77, 10.0.0.93, 10.0.0.94, 10.0.0.95, 10.0.0.96, 10.0.0.97, 10.0.0.98, 10.0.0.99");
        // Align host/port/proto lists with XFF (same count). Entry used will be at reversed index 6 (original index 3).
        $headers->set("X-Forwarded-Host", "h0, h1, h2, hostname.tld, h4, h5, h6, h7, h8, h9");
        $headers->set("X-Forwarded-Port", "5000, 5001, 5002, 6001, 5004, 5005, 5006, 5007, 5008, 5009");
        $headers->set("X-Forwarded-Proto", "http, http, http, https, http, http, http, http, http, http");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://placeholder/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp));

        // First non-trusted from right is at index 7 → client IP
        $this->assertSame("203.0.113.77", $gw->clientIp);
        // Authority promoted from nearest trusted hop (index 6)
        $this->assertSame("hostname.tld", $gw->server?->hostname);
        $this->assertSame(6001, $gw->port);
        $this->assertSame("https", $gw->scheme);
        $this->assertSame(7, $gw->proxyHop);
    }

    /**
     * @return void
     * @throws \Charcoal\Http\Router\Exceptions\RequestContextException
     */
    public function testGateway_Xff_RightAligned_UsesNearestTrustedAuthority(): void
    {
        $config = new RouterConfig(
            [
                new HttpServer("hostname.tld", 80, 443),
                new HttpServer("localhost", 80, 443)
            ],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: true,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        // XFF: left→right = client→…→nearest; we walk right→left
        $headers->set("X-Forwarded-For", "203.0.113.7, 10.9.8.7, 10.1.2.3");
        $headers->set("X-Forwarded-Host", "client.tld, localhost, proxyA");
        $headers->set("X-Forwarded-Proto", "http, https, http");
        $headers->set("X-Forwarded-Port", "1234, 443, 80");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp));
        // First non-trusted from right is 203.0.113.7 (index 2)
        $this->assertSame("203.0.113.7", $gw->clientIp);
        // Nearest trusted (index 1) supplies authority
        $this->assertSame("localhost", $gw->server?->hostname);
        $this->assertSame(443, $gw->port);
        $this->assertSame("https", $gw->scheme);
        $this->assertSame(2, $gw->proxyHop);
    }

    /**
     * @return void
     * @throws \Charcoal\Http\Router\Exceptions\RequestContextException
     */
    public function testGateway_Forwarded_Index0Client_PromotesIpOnly(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        // Client at index 0 → host/proto/port MUST NOT be promoted
        $headers->set("Forwarded", "for=203.0.113.7;proto=https;host=malicious.tld:444");

        // Request
        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $env = new GatewayEnv($peerIp, "hostname.tld", https: false);
        $gw = new TrustGateway($config, $request, $env);
        $this->assertSame("203.0.113.7", $gw->clientIp);
        $this->assertSame("hostname.tld", $gw->server?->hostname);
        $this->assertNull($gw->port);
        $this->assertSame($env->https ? "https" : "http", $gw->scheme);
        $this->assertSame(0, $gw->proxyHop);
    }

    /**
     * @return void
     * @throws \Charcoal\Http\Router\Exceptions\RequestContextException
     */
    public function testGateway_ForwardedTwoHops_PromotesClient_UsesLastTrustedAuthority(): void
    {
        // Config: one HTTPS server; proxies trust 10.0.0.0/8 and allow Forwarded
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: true,
            wwwAlias: true,
        );

        // Forwarded: nearest → farthest
        // last trusted hop (nearest) provides host/proto; next is the client
        $headers = new Headers();
        $headers->set(
            "Forwarded",
            "for=10.2.3.4;proto=https;host=hostname.tld, for=203.0.113.7"
        );

        // Request
        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        // Invoke TrustGateway::establish(...) via reflection
        $gw = new TrustGateway($config, $request, new GatewayEnv("10.1.2.3"));
        $this->assertSame("203.0.113.7", $gw->clientIp);
        $this->assertSame("hostname.tld", $gw->server->hostname);
        $this->assertNull($gw->port, "Port should not be inferred from proto");
        $this->assertSame("https", $gw->scheme);
        $this->assertSame(1, $gw->proxyHop); // client at index 1 (after one trusted hop)
    }
}