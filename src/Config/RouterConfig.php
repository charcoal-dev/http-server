<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Config;

/**
 * Class RouterConfig
 * @package Charcoal\Http\Router\Config
 */
readonly class RouterConfig
{
    public function __construct(
        public bool   $parsePayloadKeepBody = false,
        public string $parsePayloadUndefinedParam = "json"
    )
    {
    }
}