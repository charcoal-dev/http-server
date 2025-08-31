<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server;

use Charcoal\Http\Commons\Support\CorsPolicy;
use Charcoal\Http\Server\Config\RequestConstraints;
use Charcoal\Http\Server\Config\VirtualHost;
use Charcoal\Http\Server\Config\ServerConfig;
use Charcoal\Http\TrustProxy\Config\TrustedProxy;
use PHPUnit\Framework\TestCase;

/**
 * This test class is responsible for verifying the correct behavior of the Config class.
 * It contains a comprehensive set of unit tests for various scenarios related to hostnames,
 * proxies, and configuration options.
 */
final class ConfigTest extends TestCase
{
    private CorsPolicy $corsPolicy;
    private RequestConstraints $requestConstraints;

    public function setUp(): void
    {
        $this->corsPolicy = new CorsPolicy(true, []);
        $this->requestConstraints = new RequestConstraints();
    }

    public function testBuildsWithValidHostnamesAndProxiesAndDefaults(): void
    {
        $s1 = new VirtualHost("example.com");
        $s2 = new VirtualHost("*.example.com");

        $p1 = new readonly class(true, ["10.0.0.0/8"], "\x01\x02") extends TrustedProxy {
            public function __construct(bool $useForwarded, array $cidrList, private string $chk)
            {
                parent::__construct($useForwarded, $cidrList);
            }

            public function checksum(): string
            {
                return $this->chk;
            }
        };
        $p2 = new readonly class(true, ["192.168.0.0/16"], "\xAA\xBB") extends TrustedProxy {
            public function __construct(bool $useForwarded, array $cidrList, private string $chk)
            {
                parent::__construct($useForwarded, $cidrList);
            }

            public function checksum(): string
            {
                return $this->chk;
            }
        };

        $cfg = new ServerConfig([$s1, $s2], [$p1, $p2], $this->corsPolicy, $this->requestConstraints);

        $this->assertSame([$s1, $s2], $cfg->hostnames);
        $this->assertSame([$p1, $p2], $cfg->proxies);
        $this->assertTrue($cfg->wwwSupport);
    }

    public function testAllowsEmptyHostnamesAndProxies(): void
    {
        $cfg = new ServerConfig([], [], $this->corsPolicy, $this->requestConstraints);

        $this->assertSame([], $cfg->hostnames);
        $this->assertSame([], $cfg->proxies);
        $this->assertTrue($cfg->wwwSupport);
    }

    public function testAllowsEmptyProxiesWithHostnames(): void
    {
        $s1 = new VirtualHost("example.com");
        $cfg = new ServerConfig([$s1], [], $this->corsPolicy, $this->requestConstraints);

        $this->assertSame([$s1], $cfg->hostnames);
        $this->assertSame([], $cfg->proxies);
    }

    public function testRejectsNonHttpServerInHostnames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Required instance of/");

        $notServer = (object)[];
        /** @noinspection PhpParamsInspection */
        new ServerConfig([$notServer], [], $this->corsPolicy, $this->requestConstraints);
    }

    public function testRejectsDuplicateNonWildcardHostname(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate hostname/");

        $s1 = new VirtualHost("example.com");
        $s2 = new VirtualHost("example.com");

        new ServerConfig([$s1, $s2], [], $this->corsPolicy, $this->requestConstraints);
    }

    public function testRejectsDuplicateWildcardHostname(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate hostname/");

        $s1 = new VirtualHost("*.example.com");
        $s2 = new VirtualHost("*.example.com");

        new ServerConfig([$s1, $s2], [], $this->corsPolicy, $this->requestConstraints);
    }

    public function testRejectsDuplicateHostnameCaseInsensitive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate hostname/");

        $s1 = new VirtualHost("Example.COM");
        $s2 = new VirtualHost("example.com");

        new ServerConfig([$s1, $s2], [], $this->corsPolicy, $this->requestConstraints);
    }

    public function testRejectsDuplicateHostnameIgnoringTrailingDot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate hostname/");

        $s1 = new VirtualHost("example.com.");
        $s2 = new VirtualHost("example.com");

        new ServerConfig([$s1, $s2], [], $this->corsPolicy, $this->requestConstraints);
    }

    public function testRejectsDuplicateHostnameEvenIfPortsDiffer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate hostname/");

        $s1 = new VirtualHost("example.com", 80);
        $s2 = new VirtualHost("example.com", 443);

        new ServerConfig([$s1, $s2], [], $this->corsPolicy, $this->requestConstraints);
    }

    public function testAllowsSameBaseWithWildcardAndExact(): void
    {
        $sExact = new VirtualHost("example.com");
        $sWildcard = new VirtualHost("*.example.com");

        $cfg = new ServerConfig([$sExact, $sWildcard], [], $this->corsPolicy, $this->requestConstraints);
        $this->assertSame([$sExact, $sWildcard], $cfg->hostnames);
    }

    public function testAllowsWildcardAndSpecificSubdomainTogether(): void
    {
        $sWildcard = new VirtualHost("*.example.com");
        $sSub = new VirtualHost("api.example.com");

        $cfg = new ServerConfig([$sWildcard, $sSub], [], $this->corsPolicy, $this->requestConstraints);
        $this->assertSame([$sWildcard, $sSub], $cfg->hostnames);
    }

    public function testAllowsWwwAndNonWwwTogether(): void
    {
        $s1 = new VirtualHost("www.example.com");
        $s2 = new VirtualHost("example.com");

        $cfg = new ServerConfig([$s1, $s2], [], $this->corsPolicy, $this->requestConstraints);
        $this->assertSame([$s1, $s2], $cfg->hostnames);
    }

    public function testRejectsNonTrustedProxyInProxies(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Required instance of/");

        $notProxy = (object)[];
        /** @noinspection PhpParamsInspection */
        new ServerConfig([], [$notProxy], $this->corsPolicy, $this->requestConstraints);
    }

    public function testRejectsDuplicateProxiesByChecksumWithSubclass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate proxy/");

        $p1 = new readonly class(true, ["10.0.0.0/8"], "\xDE\xAD\xBE\xEF") extends TrustedProxy {
            public function __construct(bool $useForwarded, array $cidrList, private string $chk)
            {
                parent::__construct($useForwarded, $cidrList);
            }

            public function checksum(): string
            {
                return $this->chk;
            }
        };
        $p2 = new readonly class(false, ["172.16.0.0/12"], "\xDE\xAD\xBE\xEF") extends TrustedProxy {
            public function __construct(bool $useForwarded, array $cidrList, private string $chk)
            {
                parent::__construct($useForwarded, $cidrList);
            }

            public function checksum(): string
            {
                return $this->chk;
            }
        };

        new ServerConfig([], [$p1, $p2], $this->corsPolicy, $this->requestConstraints);
    }

    public function testRejectsDuplicateProxiesByChecksumWithBaseTrustedProxy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate proxy/");

        $p1 = new TrustedProxy(true, ["10.0.0.0/8", "192.168.0.0/16"]);
        $p2 = new TrustedProxy(false, ["10.0.0.0/8", "192.168.0.0/16"]); // same CIDRs -> same checksum

        new ServerConfig([], [$p1, $p2], $this->corsPolicy, $this->requestConstraints);
    }

    public function testAcceptsMultipleDistinctProxies(): void
    {
        $p1 = new TrustedProxy(true, ["10.0.0.0/8"]);
        $p2 = new TrustedProxy(true, ["192.168.0.0/16"]);
        $p3 = new TrustedProxy(true, ["172.16.0.0/12"]);

        $cfg = new ServerConfig([], [$p1, $p2, $p3], $this->corsPolicy, $this->requestConstraints);
        $this->assertSame([$p1, $p2, $p3], $cfg->proxies);
    }

    public function testwwwSupportFlagExplicitFalse(): void
    {
        $s = new VirtualHost("example.com");
        $p = new TrustedProxy(true, ["10.0.0.0/8"]);

        $cfg = new ServerConfig([$s], [$p], $this->corsPolicy, $this->requestConstraints, true, false);
        $this->assertFalse($cfg->wwwSupport);
    }

    public function testPreservesOrderOfHostnamesAndProxies(): void
    {
        $s1 = new VirtualHost("a.test");
        $s2 = new VirtualHost("*.b.test");
        $s3 = new VirtualHost("c.test");

        $p1 = new TrustedProxy(true, ["10.0.0.0/8"]);
        $p2 = new TrustedProxy(true, ["192.168.0.0/16"]);
        $p3 = new TrustedProxy(true, ["172.16.0.0/12"]);

        $hostnames = [$s1, $s2, $s3];
        $proxies = [$p1, $p2, $p3];

        $cfg = new ServerConfig($hostnames, $proxies, $this->corsPolicy, $this->requestConstraints);

        $this->assertSame($hostnames, $cfg->hostnames);
        $this->assertSame($proxies, $cfg->proxies);
    }
}