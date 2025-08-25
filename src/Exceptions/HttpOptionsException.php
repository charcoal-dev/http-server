<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Exceptions;

use Charcoal\Http\Router\Request\CorsPolicy;

/**
 * Class HttpOptionsException
 * @package Charcoal\Http\Router\Exceptions
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