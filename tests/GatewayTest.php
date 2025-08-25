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
use Charcoal\Http\Router\Config\GatewayEnv;
use Charcoal\Http\Router\Config\HttpServer;
use Charcoal\Http\Router\Config\RouterConfig;
use Charcoal\Http\Router\Config\TrustedProxy;
use Charcoal\Http\Router\Exceptions\RequestContextException;
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
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Forwarded_QuotedTokens_MixedCaseProto_PromotesAndLowercases(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        // Nearest trusted provides quoted/mixed-case proto + quoted host; next is client
        $headers->set("Forwarded", 'for=10.9.8.7;proto="HTTPS";host="hostname.tld", for=203.0.113.7');

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld", https: false));
        $this->assertSame("203.0.113.7", $gw->clientIp);
        $this->assertSame("hostname.tld", $gw->server?->hostname);
        $this->assertNull($gw->port);
        $this->assertSame("https", $gw->scheme); // lowercased
        $this->assertSame(1, $gw->proxyHop);
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Xff_Ipv4WithPort_StripsPortAndKeepsBaselineAuthority(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        // Client token includes :port → should be stripped
        $headers->set("X-Forwarded-For", "203.0.113.7:51111, 10.1.2.3");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld"));
        $this->assertSame("203.0.113.7", $gw->clientIp);
        $this->assertSame("hostname.tld", $gw->server?->hostname); // baseline retained
        $this->assertNull($gw->port);
        $this->assertSame("http", $gw->scheme);
        $this->assertSame(1, $gw->proxyHop);
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Forwarded_TrustedThenClient_UsesLastTrustedAuthority(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        // nearest (trusted) carries proto/host; next is client
        $headers->set("Forwarded", "for=10.9.8.7;proto=https;host=hostname.tld, for=203.0.113.7");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld", https: false));
        $this->assertSame("203.0.113.7", $gw->clientIp);
        $this->assertSame("hostname.tld", $gw->server?->hostname);
        $this->assertNull($gw->port);
        $this->assertSame("https", $gw->scheme);
        $this->assertSame(1, $gw->proxyHop);
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Forwarded_AllTrusted_NoPromotion(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        $headers->set("Forwarded", "for=10.2.3.4, for=10.3.4.5");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld"));
        $this->assertSame($peerIp, $gw->clientIp);    // no promotion
        $this->assertSame("hostname.tld", $gw->server?->hostname);
        $this->assertNull($gw->port);
        $this->assertSame("http", $gw->scheme);
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Forwarded_PeerUntrusted_IgnoresHeader(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "192.0.2.10"; // untrusted
        $headers = new Headers();
        $headers->set("Forwarded", "for=203.0.113.7;proto=https;host=hostname.tld");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld"));
        $this->assertSame($peerIp, $gw->clientIp);
        $this->assertSame("http", $gw->scheme); // unchanged
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Forwarded_InvalidAndObfuscated_SkipsUntilValid(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        $headers->set("Forwarded", "for=_hidden, for=garbage, for=203.0.113.7");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld"));
        $this->assertSame("203.0.113.7", $gw->clientIp);
        $this->assertSame(2, $gw->proxyHop);
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Forwarded_Ipv6Index0_PromotesIpOnly(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        // Index 0 client (IPv6 with port in header) → IP only promoted
        $headers->set("Forwarded", 'for="[2001:db8::2]:51234";proto=https;host=malicious.tld');

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld"));
        $this->assertSame("2001:db8::2", $gw->clientIp);
        $this->assertSame("hostname.tld", $gw->server?->hostname);
        $this->assertNull($gw->port);
        $this->assertSame("http", $gw->scheme);
        $this->assertSame(0, $gw->proxyHop);
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Xff_BasicTwoHops_PromotesClientKeepsBaselineAuthority(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        $headers->set("X-Forwarded-For", "203.0.113.7, 10.1.2.3");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld"));
        $this->assertSame("203.0.113.7", $gw->clientIp);
        $this->assertSame("hostname.tld", $gw->server?->hostname); // baseline
        $this->assertNull($gw->port);
        $this->assertSame("http", $gw->scheme);
        $this->assertSame(1, $gw->proxyHop);
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Xff_RightAligned_UsesNearestTrustedAuthority_Short(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        $headers->set("X-Forwarded-For", "203.0.113.7, 10.9.8.7, 10.1.2.3");
        $headers->set("X-Forwarded-Host", "client.tld, hostname.tld, proxyA");
        $headers->set("X-Forwarded-Port", "1234, 443, 80");
        $headers->set("X-Forwarded-Proto", "http, https, http");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://placeholder/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp));
        $this->assertSame("203.0.113.7", $gw->clientIp);
        $this->assertSame("hostname.tld", $gw->server?->hostname); // from trustedIdx=1
        $this->assertSame(443, $gw->port);
        $this->assertSame("https", $gw->scheme);
        $this->assertSame(2, $gw->proxyHop);
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Xff_MaxHopsCap_NoPromotionBeyondCap(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"], maxHops: 1)],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        // client would be 2 hops to the left; cap prevents reaching it
        $headers->set("X-Forwarded-For", "203.0.113.7, 10.9.8.7, 10.1.2.3");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld"));
        $this->assertSame($peerIp, $gw->clientIp); // no promotion due to cap
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_BothHeaders_ForwardedWinsOverXff(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        // Forwarded says client=203.0.113.7, proto=https, host=hostname.tld
        $headers->set("Forwarded", "for=10.9.8.7;proto=https;host=hostname.tld, for=203.0.113.7");
        // XFF chain would also resolve, but we expect Forwarded to take precedence
        $headers->set("X-Forwarded-For", "203.0.113.88, 10.9.8.7, 10.1.2.3");
        $headers->set("X-Forwarded-Host", "client.tld, proxyB, proxyA");
        $headers->set("X-Forwarded-Port", "1234, 80, 80");
        $headers->set("X-Forwarded-Proto", "http, http, http");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://placeholder/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp));
        $this->assertSame("203.0.113.7", $gw->clientIp);   // from Forwarded
        $this->assertSame("hostname.tld", $gw->server?->hostname);
        $this->assertNull($gw->port);                      // no explicit port in Forwarded host
        $this->assertSame("https", $gw->scheme);           // Forwarded proto wins
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Xff_AllTrusted_NoPromotion(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "10.1.2.3"; // trusted
        $headers = new Headers();
        $headers->set("X-Forwarded-For", "10.2.3.4, 10.1.2.3");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld"));
        $this->assertSame($peerIp, $gw->clientIp);
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Xff_UntrustedPeer_IgnoresXff(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["10.0.0.0/8"])],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "192.0.2.10"; // untrusted
        $headers = new Headers();
        $headers->set("X-Forwarded-For", "203.0.113.7, 10.1.2.3");

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld"));
        $this->assertSame($peerIp, $gw->clientIp);
        $this->assertSame("http", $gw->scheme);
    }

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

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Cloudflare_SingleHop_PromotesIp_ProtoFromXfp_Https(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["173.245.48.0/20", "103.21.244.0/22"], protoFromTrustedEdge: true)], // sample CF CIDRs
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "173.245.48.5"; // Cloudflare edge (trusted)
        $headers = new Headers();
        $headers->set("X-Forwarded-For", "203.0.113.7");   // single-hop → clientIdx=0
        $headers->set("X-Forwarded-Proto", "https");       // proto override
        // no X-Forwarded-Host/Port → keep baseline authority

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld", https: false));

        $this->assertSame("203.0.113.7", $gw->clientIp);
        $this->assertSame("hostname.tld", $gw->server?->hostname); // baseline host
        $this->assertNull($gw->port);                               // no explicit port
        $this->assertSame("https", $gw->scheme);                    // from X-Forwarded-Proto
        $this->assertSame(0, $gw->proxyHop);                        // index 0
    }

    /**
     * @return void
     * @throws RequestContextException
     */
    public function testGateway_Cloudflare_SingleHop_PromotesIp_ProtoFromXfp_Http(): void
    {
        $config = new RouterConfig(
            [new HttpServer("hostname.tld", 80, 443)],
            [new TrustedProxy(true, ["173.245.48.0/20", "103.21.244.0/22"], protoFromTrustedEdge: true)],
            enforceTls: false,
            wwwAlias: true,
        );

        $peerIp = "173.245.48.5"; // Cloudflare edge (trusted)
        $headers = new Headers();
        $headers->set("X-Forwarded-For", "203.0.113.7");
        $headers->set("X-Forwarded-Proto", "http");        // stays http
        // no X-Forwarded-Host/Port → keep baseline authority

        $request = new ServerRequest(
            HttpMethod::GET,
            HttpProtocol::Version2,
            $headers->toImmutable(),
            new UrlInfo("http://hostname.tld/"),
            isSecure: false
        );

        $gw = new TrustGateway($config, $request, new GatewayEnv($peerIp, "hostname.tld", https: false));

        $this->assertSame("203.0.113.7", $gw->clientIp);
        $this->assertSame("hostname.tld", $gw->server?->hostname);
        $this->assertNull($gw->port);
        $this->assertSame("http", $gw->scheme);            // from X-Forwarded-Proto
        $this->assertSame(0, $gw->proxyHop);
    }
}