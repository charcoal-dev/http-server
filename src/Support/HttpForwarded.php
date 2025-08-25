<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Support;

use Charcoal\Http\Commons\Support\HttpHelper;

/**
 * This abstract readonly class provides methods to parse and extract
 * proxy-related information from HTTP headers, specifically from the
 * Forwarded header.
 */
abstract readonly class HttpForwarded
{
    /**
     * Extracts and processes proxy information from the provided header
     * string up to the specified maximum number of hops.
     */
    public static function getProxies(string $header, int $maxHop): array|false
    {
        if (!$header || $maxHop < 1) {
            return false;
        }

        $proxies = explode(",", preg_replace("/[\"'\s]/", "", $header));
        $forwarded = [];
        $count = 0;
        foreach ($proxies as $proxy) {
            $count++;
            if ($count > $maxHop) {
                break;
            }

            $proxy = explode(";", $proxy);
            $match = [];
            foreach ($proxy as $part) {
                $value = match (true) {
                    str_starts_with($part, "for="),
                    str_starts_with($part, "host="),
                    str_starts_with($part, "proto="),
                    str_starts_with($part, "by=") => explode("=", $part) ?? null,
                    default => null,
                };

                if ($value && isset($value[1]) && !isset($match[$value[0]])) {
                    $match[$value[0]] = $value[1];
                }
            }

            foreach (["for", "host", "by"] as $key) {
                if (!isset($match[$key])) {
                    continue;
                }

                $hostname = HttpHelper::normalizeHostname($match[$key]);
                $match[$key] = match (true) {
                    is_string($hostname) => $hostname,
                    is_array($hostname) => $hostname[1] ? implode(":", $hostname) : $hostname[0],
                    default => null,
                };

                if (!$match[$key]) {
                    unset($match[$key]);
                }
            }

            if (isset($match["proto"])) {
                if (!in_array($match["proto"], ["http", "https", "ws", "wss"])) {
                    unset($match["proto"]);
                }
            }

            if ($match) {
                $forwarded[] = $match;
            }
        }

        return $forwarded;
    }
}