<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions;

use Authorization\CorsPolicy;

/**
 * Class HttpOptionsException
 * @package Charcoal\Http\Server\Exceptions
 */
final class HttpOptionsException extends \Exception
{
    public function __construct(
        public readonly ?string     $allowedOrigin = null,
        public readonly ?CorsPolicy $corsPolicy = null
    )
    {
        parent::__construct("HTTP OPTIONS");
    }
}