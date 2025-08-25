<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

use Charcoal\Http\Commons\Contracts\HeadersInterface;
use Charcoal\Http\Commons\Support\HttpHelper;
use Charcoal\Http\Router\Config\RouterConfig;
use Charcoal\Http\Router\Config\HttpServer;
use Charcoal\Http\Router\Config\TrustedProxy;
use Charcoal\Http\Router\Enums\RequestError;
use Charcoal\Http\Router\Exceptions\RequestContextException;
use Charcoal\Http\Router\Request\Result\RedirectUrl;
use Charcoal\Http\Router\Support\HttpForwarded;

/**
 * The TrustGateway class is responsible for determining the client's IP address and scheme
 * while optionally processing trusted proxy information. It examines environmental variables,
 * headers, and configuration to validate the client and potentially traverse through
 * trusted proxies specified via configuration.
 */
final readonly class TrustGateway
{
    public ?TrustedProxy $proxy;
    public ?int $proxyHop;
    public ?HttpServer $server;
    public string $clientIp;
    public ?int $port;
    public ?string $scheme;

    /**
     * @throws RequestContextException
     */
    public function __construct(RouterConfig $config, ServerRequest $request, GatewayEnv $env = new GatewayEnv())
    {
        $peerIpBinary = @inet_pton($env->peerIp ?? "");
        if ($peerIpBinary === false) {
            throw new RequestContextException(RequestError::BadPeerIp,
                new \RuntimeException("Invalid peer IP: " . $env->peerIp));
        }

        $port = $env->port;
        $hostname = $env->hostname;
        $scheme = $env->https ? "https" : "http";
        $checkTrustedProxies = $this->checkProxies($peerIpBinary, $config, $request->headers);
        if ($checkTrustedProxies) {
            $this->clientIp = $checkTrustedProxies[0];
            $hostname = $checkTrustedProxies[1] ?: $hostname;
            $port = $checkTrustedProxies[2] ?: $port;
            $scheme = $checkTrustedProxies[3] ?: $scheme;
            $this->proxyHop = $checkTrustedProxies[4];
            $this->proxy = $checkTrustedProxies[5];
        } else {
            $this->clientIp = $env->peerIp;
            $this->proxyHop = null;
            $this->proxy = null;
        }

        if (!$this->proxy) {
            // Validation default values only; Proxy returns pre-validated data
            [$defaultHost, $suggestedPort] = HttpHelper::normalizeHostnamePort($hostname) ?: [null, null];
            $port = $port ?? $suggestedPort;
            $hostname = $defaultHost;
        }

        $this->scheme = $scheme && in_array(strtolower($scheme), ["http", "https"], true) ?
            strtolower($scheme) : null;
        $this->port = is_int($port) && $port > 0 && $port < 65536 ? $port : null;
        if (!$hostname) {
            throw new RequestContextException(RequestError::BadHostname,
                new \RuntimeException("Invalid hostname: " . ($hostname ?? "!null")));
        }

        $validateAs = $hostname;
        if ($config->wwwAlias && str_starts_with($hostname, "www.")) {
            $validateAs = substr($hostname, 4);
        }

        foreach ($config->hostnames as $profile) {
            $configServer = $profile->matches($validateAs, $this->port);
            if ($configServer) {
                $this->server = $profile;
                break;
            }
        }

        if (!isset($this->server)) {
            throw new RequestContextException(RequestError::IncorrectHost,
                new \RuntimeException("Hostname not configured: " . $hostname));
        }

        if ($config->enforceTls && $this->scheme !== "https") {
            throw RequestContextException::forRedirect(RequestError::TlsEnforcedRedirect,
                new RedirectUrl($request->url, 308, null, toggleScheme: true, absolute: false, queryStr: true));
        }
    }

    /**
     * @param string $peerIpBinary
     * @param RouterConfig $config
     * @param HeadersInterface $headers
     * @return array<string,string|null,int|null,string|null,int,TrustedProxy>|false
     */
    private function checkProxies(string $peerIpBinary, RouterConfig $config, HeadersInterface $headers): array|false
    {
        if (!$config->proxies) {
            return false;
        }

        // Convert literal to binary form
        $matched = false;
        foreach ($config->proxies as $proxy) {
            $matched = $proxy->match($peerIpBinary);
            if ($matched) {
                break;
            }
        }

        if (!$matched || !isset($proxy)) {
            return false;
        }

        // User "Forwarded" header if available, and enabled
        if ($proxy->useForwarded) {
            $forwarded = $headers->get("Forwarded") ?? "";
            $matched = $this->checkForwarded($proxy, $forwarded, $proxy->maxHops);
            if ($matched) {
                return [...$matched, $proxy];
            }
        }

        $xff = $this->checkXFF($proxy, $headers, $proxy->maxHops);
        if (!$xff) {
            return false;
        }

        [$clientIp, $hostname, $port, $scheme, $proxyHop] = $xff;
        $scheme = $scheme && in_array(strtolower($scheme), ["http", "https"]) ?
            $scheme : null;

        if (is_string($port) && ctype_digit($port)) {
            $port = (int)$port;
        }

        if ($port && ($port < 1 || $port > 65535)) {
            $port = null;
        }

        if ($hostname) {
            $hostname = HttpHelper::normalizeHostnamePort($hostname) ?: [null, null];
            if ($hostname) {
                $port = $port ?? $hostname[1] ?? null;
                $hostname = $hostname[0] ?? null;
            }
        }

        return [$clientIp, $hostname, $port, $scheme, $proxyHop, $proxy];
    }

    /**
     * @param TrustedProxy $proxy
     * @param HeadersInterface $headers
     * @param int $maxHops
     * @return array<string,string|null,int|null,string|null,int>|false
     */
    private function checkXFF(TrustedProxy $proxy, HeadersInterface $headers, int $maxHops): array|false
    {
        $xff = $headers->get("X-Forwarded-For");
        if (!$xff) {
            return false;
        }

        $clientIp = null;
        $ips = array_reverse(array_map(fn($a) => trim($a, " \t\"'"), explode(",", $xff)));
        if (!$ips) {
            return false;
        }

        $index = -1;
        foreach ($ips as $ip) {
            $index++;
            if ($index >= $maxHops) {
                break;
            }

            $ip = trim($ip);
            if ($ip === "" || strcasecmp($ip, "unknown") === 0) {
                continue;
            }

            // Strip IPv4 ":port" if present (inet_pton can't handle it)
            if (preg_match("/^\d{1,3}(?:\.\d{1,3}){3}:\d+$/", $ip)) {
                $ip = strstr($ip, ":", true);
            }

            $ipBinary = @inet_pton($ip);
            if ($ipBinary === false) {
                continue;
            }

            if (!$proxy->match($ipBinary)) {
                $clientIp = $ip;
                break;
            }
        }

        if (!$clientIp) {
            return false;
        }

        $xfp2 = array_reverse(array_map("trim", explode(",", $headers->get("X-Forwarded-Proto") ?? "")));
        if ($index === 0) {
            $scheme = match($proxy->protoFromTrustedEdge) {
                true => $xfp2[0] ?? null,
                false => null,
            };

            return [$clientIp, null, null, $scheme, 0];
        }

        $xfh = array_reverse(array_map("trim", explode(",", $headers->get("X-Forwarded-Host") ?? "")));
        $xfp1 = array_reverse(array_map("trim", explode(",", $headers->get("X-Forwarded-Port") ?? "")));

        return [
            $clientIp,
            $xfh[$index - 1] ?? ($xfh[0] ?? null),
            $xfp1[$index - 1] ?? ($xfp1[0] ?? null),
            $xfp2[$index - 1] ?? ($xfp2[0] ?? null),
            $index
        ];
    }

    /**
     * @param TrustedProxy $proxy
     * @param string $header
     * @param int $maxHops
     * @return array<string,string|null,int|null,string|null,int>|false
     */
    private function checkForwarded(TrustedProxy $proxy, string $header, int $maxHops): array|false
    {
        if (!$header) {
            return false;
        }

        $hostname = null;
        $port = null;
        $scheme = null;
        $entries = HttpForwarded::getProxies($header, $maxHops) ?: [];
        $index = -1;
        foreach ($entries as $channel) {
            $index++;
            if (!isset($channel["for"])) {
                continue;
            }

            $channelIp = (HttpHelper::normalizeHostnamePort($channel["for"]) ?: [null])[0];
            if (!$channelIp) {
                continue;
            }

            $channelIpBinary = @inet_pton($channelIp);
            if ($channelIpBinary === false) {
                continue;
            }

            if (!$proxy->match($channelIpBinary)) {
                if ($index === 0) {
                    return [$channelIp, null, null, null, 0];
                }

                return [$channelIp, $hostname ?? null, $port ?? null, $scheme ?? null, $index];
            }

            if (isset($channel["host"])) {
                [$entryHost, $entryPort] = HttpHelper::normalizeHostnamePort($channel["host"]) ?: [null, null];
                if ($entryHost) {
                    $hostname = $entryHost;
                    $port = $entryPort;
                }
            }

            if (isset($channel["proto"]) && in_array(strtolower($channel["proto"]), ["http", "https"])) {
                $scheme = $channel["proto"];
            }
        }

        return false;
    }
}