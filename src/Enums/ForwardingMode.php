<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Enums;

/**
 * The ForwardingMode enum defines two modes:
 * - DNAT: Refers to Destination Network Address Translation.
 * - ReverseProxy: Refers to traffic being forwarded through a reverse proxy server.
 */
enum ForwardingMode
{
    case DNAT;
    case ReverseProxy;
}