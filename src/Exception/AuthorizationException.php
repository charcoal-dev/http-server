<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Exception;

use Charcoal\Http\Commons\Exception\HttpException;
use Charcoal\Http\Router\Authorization\AuthError;

/**
 * Class AuthorizationException
 * @package Charcoal\Http\Router\Exception
 */
class AuthorizationException extends HttpException
{
    public function __construct(
        public readonly AuthError $error,
        ?\Throwable               $previous = null
    )
    {
        parent::__construct($this->error->name, $this->error->value, $previous);
    }
}
