<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Request;

/**
 * Exception thrown when a request is terminated due to a TLS requirement.
 */
final class TlsRequiredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("Connection is not secure while TLS is enforced");
    }
}