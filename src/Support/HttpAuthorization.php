<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Support;

use Charcoal\Base\Charsets\Ascii;
use Charcoal\Http\Commons\Contracts\HeadersInterface;

/**
 * Class HttpAuthorization
 * @package Charcoal\Http\Server\Support
 */
readonly class HttpAuthorization
{
    /** @var array<string,string|array<string,string>> */
    public array $schemes;
    /** @var string[] */
    public array $invalid;
    public string $unchecked;

    /**
     * @param HeadersInterface $headers
     */
    public function __construct(HeadersInterface $headers)
    {
        $authorization = trim(Ascii::sanitizeUseFilter($headers->get("Authorization") ?? ""));
        if (!$authorization) {
            $this->schemes = [];
            $this->invalid = [];
            $this->unchecked = "";
            return;
        }

        list($valid, $this->invalid, $this->unchecked) = static::from($authorization);
        $schemes = [];
        if ($valid) {
            foreach ($valid as $scheme) {
                $schemes[strtolower($scheme[0])] = $scheme[1];
            }
        }

        $this->schemes = $schemes;
    }

    /**
     * @param string $authorization
     * @return array
     */
    public static function from(string $authorization): array
    {
        $valid = [];
        $invalid = [];
        $unchecked = "";
        static::parseSchemes($authorization, $valid, $invalid, $unchecked);
        return [$valid, $invalid, $unchecked];
    }

    /**
     * @param string $authorizations
     * @param array $valid
     * @param array $invalid
     * @param string $unchecked
     * @return void
     */
    private static function parseSchemes(string $authorizations, array &$valid, array &$invalid, string &$unchecked): void
    {
        $authorizations = trim($authorizations);
        if (!$authorizations) {
            return;
        }

        $authorizations = explode(",", $authorizations, 2);
        $credentials = trim(array_shift($authorizations));
        $authorizations = trim($authorizations[0] ?? "");

        $credentials = explode(" ", trim($credentials), 2);
        $scheme = trim(array_shift($credentials));
        $credentials = trim($credentials[0] ?? "");

        if (!$scheme || !$credentials) {
            if ($scheme || $credentials) {
                $invalid[] = $scheme . " " . $credentials;
            }

            static::parseSchemes($authorizations, $valid, $invalid, $unchecked);
            return;
        }

        // No length check, just checks for base64 charset (covers base16 encoded tokens too)
        if (preg_match('/^[A-Za-z0-9+\/\-_]+={0,3}$/', $credentials) === 1) {
            $valid[] = [$scheme, $credentials];
            static::parseSchemes($authorizations, $valid, $invalid, $unchecked);
            return;
        }

        $credentials = $credentials . ", " . $authorizations;
        $regExp = '/\s*([A-Za-z][A-Za-z0-9+.\-]*)\s*=\s*(?:"((?:[^"\\\\]|\\\\.)*)"|\'((?:[^\'\\\\]|\\\\.)*)\'|([^,]*?))\s*(?:,|$)/';
        if (preg_match_all($regExp, $credentials, $matched, PREG_SET_ORDER)) {
            $authorizations = preg_replace($regExp, "", $credentials);
            if ($matched) {
                $valid[] = [$scheme, static::parseCredentials($matched)];
            }

            static::parseSchemes($authorizations, $valid, $invalid, $unchecked);
            return;
        }

        $unchecked = $scheme . " " . $credentials;
    }

    /**
     * @param array $matches
     * @return array
     */
    private static function parseCredentials(array $matches): array
    {
        $result = [];
        foreach ($matches as $entry) {
            $name = $entry[1];
            $value = $entry[2] !== "" ? $entry[2] : ($entry[3] !== "" ? $entry[3] : trim($entry[4]));
            $value = preg_replace('/\\\\(["\'\\\\])/', "$1", $value);
            $result[$name] = $value;
        }

        return $result;
    }
}