<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Exceptions;

use Charcoal\Http\Router\Enums\RequestError;
use Charcoal\Http\Router\Request\Result\RedirectUrl;

/**
 * An exception specifically designed to handle errors related to the request context.
 * It extends the base Exception class and provides additional details such as the request error instance
 * and an optional redirect target.
 */
final class RequestContextException extends \Exception
{
    public function __construct(
        public readonly RequestError $error,
        ?\Throwable                  $previous,
        public readonly ?RedirectUrl $redirectTo = null,
    )
    {
        parent::__construct($previous?->getMessage() ?? $this->error->name, 0, $previous);
    }

    /**
     * @param RequestError $error
     * @param RedirectUrl $redirectTo
     * @return self
     */
    public static function forRedirect(RequestError $error, RedirectUrl $redirectTo): self
    {
        return new self($error, null, $redirectTo);
    }
}