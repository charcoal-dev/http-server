<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server\Support;

use Charcoal\Http\TrustProxy\ForwardedHeaderParser;

/**
 * This class contains tests for processing HTTP Forwarded headers to extract proxy information.
 * It verifies the `HttpForwarded::getProxies()` functionality under various scenarios, including:
 * - IPv4 addresses with additional parameters such as protocol and host.
 * - IPv6 addresses (both bracketed and unbracketed) with potential parameter validation.
 * - Handling of invalid or unbracketed IPv6 addresses by skipping invalid elements.
 */
class HttpForwardedTest extends \PHPUnit\Framework\TestCase
{
    public function testGetProxies()
    {
        $this->assertSame([["for" => "198.51.100.17", "proto" => "https", "host" => "example.com:443"]],
            ForwardedHeaderParser::getProxies("for=198.51.100.17;proto=https;host=example.com:443", 1),
            "Forwarded: IPv4 + proto + host");
        $this->assertSame([["for" => null, "proto" => "https"]], ForwardedHeaderParser::getProxies("for=2001:db8::1;proto=https", 1),
            "Forwarded: unbracketed IPv6 should be rejected");
        $this->assertSame([["for" => "2001:db8::1", "proto" => "http"]],
            ForwardedHeaderParser::getProxies('for="[2001:db8::1]";proto=http', 1),
            "Forwarded: IPv6 (bracketed) + proto");
        $this->assertSame([["for" => null, "proto" => "https"]],
            ForwardedHeaderParser::getProxies("for=2001:db8::1;proto=https", 1),
            "Forwarded: unbracketed IPv6 → keep element, IP becomes null");
        $this->assertSame([["for" => "198.51.100.17", "proto" => "http"],
            ["for" => "203.0.113.5", "proto" => "https"]],
            ForwardedHeaderParser::getProxies("for=198.51.100.17;proto=http, for=203.0.113.5;proto=https", 2),
            "Forwarded: pick rightmost (nearest) hop with max_hops=1");
        $this->assertSame([["for" => "198.51.100.17", "proto" => "https"]],
            ForwardedHeaderParser::getProxies("for=198.51.100.17;for=203.0.113.5;proto=https", 1),
            "Forwarded: duplicate param inside element → keep first");
        $this->assertSame([["host" => "api.example.com:8443"]],
            ForwardedHeaderParser::getProxies('host="api.example.com:8443"', 1),
            "Forwarded: quoted-string value should be unquoted");
        $this->assertSame([["proto" => "http"], ["proto" => "https"]],
            ForwardedHeaderParser::getProxies("proto=http, proto=https", 2),
            "Forwarded: two hops clipped by max_hops=2, nearest first");
        $this->assertSame([["host" => "api.example.com:8443"]],
            ForwardedHeaderParser::getProxies('host="api.example.com:8443"', 1),
            "Forwarded: quoted host unquoted");
        $this->assertSame([["for" => "198.51.100.17", "proto" => "https"]],
            ForwardedHeaderParser::getProxies('for=198.51.100.17;for=203.0.113.5;proto=https', 1),
            "Forwarded: duplicate param keeps first");
        $this->assertSame([["for" => null, "proto" => "https", "host" => "api.example.com"]],
            ForwardedHeaderParser::getProxies('for=2001:db8::1;proto=https;host=api.example.com', 1),
            "Forwarded: bad IPv6 in for → keep other params");
        $this->assertSame([["for" => null]],
            ForwardedHeaderParser::getProxies('for=2001:db8::1', 1),
            "Forwarded: invalid-only element → nullified");
        $this->assertSame([["proto" => "http"]],
            ForwardedHeaderParser::getProxies('proto=http, proto=https', 1),
            "Forwarded: max_hops=1 clips to first element");
        $this->assertSame([["for" => null]],
            ForwardedHeaderParser::getProxies('for=_hidden', 1),
            "Forwarded: obfuscated for token is removed");
    }
}