<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Exceptions;

use Charcoal\Http\Commons\Exceptions\HttpException;
use Charcoal\Http\Router\Authorization\AuthError;

/**
 * Class AuthenticationException
 * @package Charcoal\Http\Router\Exceptions
 */
class AuthenticationException extends HttpException
{
    public function __construct(
        public readonly AuthError $error,
        ?\Throwable               $previous = null
    )
    {
        parent::__construct($this->error->name, $this->error->value, $previous);
    }
}
