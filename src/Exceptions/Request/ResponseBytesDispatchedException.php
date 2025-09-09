<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Request;

/**
 * Exception thrown when response bytes are successfully dispatched.
 */
final class ResponseBytesDispatchedException extends \Exception
{
    /** @internal */
    public function __construct()
    {
        parent::__construct();
    }
}