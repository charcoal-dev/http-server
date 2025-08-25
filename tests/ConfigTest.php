<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Router;

use Charcoal\Http\Router\Config\RouterConfig;
use Charcoal\Http\Router\Config\HttpServer;
use Charcoal\Http\Router\Config\TrustedProxy;
use PHPUnit\Framework\TestCase;

/**
 * This test class is responsible for verifying the correct behavior of the Config class.
 * It contains a comprehensive set of unit tests for various scenarios related to hostnames,
 * proxies, and configuration options.
 */
final class ConfigTest extends TestCase
{
    public function testBuildsWithValidHostnamesAndProxiesAndDefaults(): void
    {
        $s1 = new HttpServer("example.com");
        $s2 = new HttpServer("*.example.com");

        $p1 = new readonly class(true, ["10.0.0.0/8"], "\x01\x02") extends TrustedProxy {
            public function __construct(bool $useForwarded, array $cidrList, private string $chk) {
                parent::__construct($useForwarded, $cidrList);
            }
            public function checksum(): string { return $this->chk; }
        };
        $p2 = new readonly class(true, ["192.168.0.0/16"], "\xAA\xBB") extends TrustedProxy {
            public function __construct(bool $useForwarded, array $cidrList, private string $chk) {
                parent::__construct($useForwarded, $cidrList);
            }
            public function checksum(): string { return $this->chk; }
        };

        $cfg = new RouterConfig([$s1, $s2], [$p1, $p2]);

        $this->assertSame([$s1, $s2], $cfg->hostnames);
        $this->assertSame([$p1, $p2], $cfg->proxies);
        $this->assertTrue($cfg->wwwAlias);
    }

    public function testAllowsEmptyHostnamesAndProxies(): void
    {
        $cfg = new RouterConfig([], []);

        $this->assertSame([], $cfg->hostnames);
        $this->assertSame([], $cfg->proxies);
        $this->assertTrue($cfg->wwwAlias);
    }

    public function testAllowsEmptyProxiesWithHostnames(): void
    {
        $s1 = new HttpServer("example.com");
        $cfg = new RouterConfig([$s1], []);

        $this->assertSame([$s1], $cfg->hostnames);
        $this->assertSame([], $cfg->proxies);
    }

    public function testRejectsNonHttpServerInHostnames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Required instance of/");

        $notServer = (object)[];
        /** @noinspection PhpParamsInspection */
        new RouterConfig([$notServer], []);
    }

    public function testRejectsDuplicateNonWildcardHostname(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate hostname/");

        $s1 = new HttpServer("example.com");
        $s2 = new HttpServer("example.com");

        new RouterConfig([$s1, $s2], []);
    }

    public function testRejectsDuplicateWildcardHostname(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate hostname/");

        $s1 = new HttpServer("*.example.com");
        $s2 = new HttpServer("*.example.com");

        new RouterConfig([$s1, $s2], []);
    }

    public function testRejectsDuplicateHostnameCaseInsensitive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate hostname/");

        $s1 = new HttpServer("Example.COM");
        $s2 = new HttpServer("example.com");

        new RouterConfig([$s1, $s2], []);
    }

    public function testRejectsDuplicateHostnameIgnoringTrailingDot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate hostname/");

        $s1 = new HttpServer("example.com.");
        $s2 = new HttpServer("example.com");

        new RouterConfig([$s1, $s2], []);
    }

    public function testRejectsDuplicateHostnameEvenIfPortsDiffer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate hostname/");

        $s1 = new HttpServer("example.com", 80);
        $s2 = new HttpServer("example.com", 443);

        new RouterConfig([$s1, $s2], []);
    }

    public function testAllowsSameBaseWithWildcardAndExact(): void
    {
        $sExact = new HttpServer("example.com");
        $sWildcard = new HttpServer("*.example.com");

        $cfg = new RouterConfig([$sExact, $sWildcard], []);
        $this->assertSame([$sExact, $sWildcard], $cfg->hostnames);
    }

    public function testAllowsWildcardAndSpecificSubdomainTogether(): void
    {
        $sWildcard = new HttpServer("*.example.com");
        $sSub = new HttpServer("api.example.com");

        $cfg = new RouterConfig([$sWildcard, $sSub], []);
        $this->assertSame([$sWildcard, $sSub], $cfg->hostnames);
    }

    public function testAllowsWwwAndNonWwwTogether(): void
    {
        $s1 = new HttpServer("www.example.com");
        $s2 = new HttpServer("example.com");

        $cfg = new RouterConfig([$s1, $s2], []);
        $this->assertSame([$s1, $s2], $cfg->hostnames);
    }

    public function testRejectsNonTrustedProxyInProxies(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Required instance of/");

        $notProxy = (object)[];
        /** @noinspection PhpParamsInspection */
        new RouterConfig([], [$notProxy]);
    }

    public function testRejectsDuplicateProxiesByChecksumWithSubclass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate proxy/");

        $p1 = new readonly class(true, ["10.0.0.0/8"], "\xDE\xAD\xBE\xEF") extends TrustedProxy {
            public function __construct(bool $useForwarded, array $cidrList, private string $chk) {
                parent::__construct($useForwarded, $cidrList);
            }
            public function checksum(): string { return $this->chk; }
        };
        $p2 = new readonly class(false, ["172.16.0.0/12"], "\xDE\xAD\xBE\xEF") extends TrustedProxy {
            public function __construct(bool $useForwarded, array $cidrList, private string $chk) {
                parent::__construct($useForwarded, $cidrList);
            }
            public function checksum(): string { return $this->chk; }
        };

        new RouterConfig([], [$p1, $p2]);
    }

    public function testRejectsDuplicateProxiesByChecksumWithBaseTrustedProxy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Duplicate proxy/");

        $p1 = new TrustedProxy(true, ["10.0.0.0/8", "192.168.0.0/16"]);
        $p2 = new TrustedProxy(false, ["10.0.0.0/8", "192.168.0.0/16"]); // same CIDRs -> same checksum

        new RouterConfig([], [$p1, $p2]);
    }

    public function testAcceptsMultipleDistinctProxies(): void
    {
        $p1 = new TrustedProxy(true, ["10.0.0.0/8"]);
        $p2 = new TrustedProxy(true, ["192.168.0.0/16"]);
        $p3 = new TrustedProxy(true, ["172.16.0.0/12"]);

        $cfg = new RouterConfig([], [$p1, $p2, $p3]);
        $this->assertSame([$p1, $p2, $p3], $cfg->proxies);
    }

    public function testWwwAliasFlagExplicitFalse(): void
    {
        $s = new HttpServer("example.com");
        $p = new TrustedProxy(true, ["10.0.0.0/8"]);

        $cfg = new RouterConfig([$s], [$p], true, false);
        $this->assertFalse($cfg->wwwAlias);
    }

    public function testPreservesOrderOfHostnamesAndProxies(): void
    {
        $s1 = new HttpServer("a.test");
        $s2 = new HttpServer("*.b.test");
        $s3 = new HttpServer("c.test");

        $p1 = new TrustedProxy(true, ["10.0.0.0/8"]);
        $p2 = new TrustedProxy(true, ["192.168.0.0/16"]);
        $p3 = new TrustedProxy(true, ["172.16.0.0/12"]);

        $hostnames = [$s1, $s2, $s3];
        $proxies = [$p1, $p2, $p3];

        $cfg = new RouterConfig($hostnames, $proxies);

        $this->assertSame($hostnames, $cfg->hostnames);
        $this->assertSame($proxies, $cfg->proxies);
    }
}