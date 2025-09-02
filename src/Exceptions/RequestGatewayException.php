<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions;

use Charcoal\Http\Server\Contracts\RequestErrorCodeInterface;
use Charcoal\Http\Server\Request\Result\RedirectUrl;

/**
 * An exception specifically designed to handle errors related to the request context.
 * It extends the base Exception class and provides additional details such as the request error instance
 * and an optional redirect target.
 */
class RequestGatewayException extends \Exception
{
    public function __construct(
        public readonly RequestErrorCodeInterface $error,
        ?\Throwable                               $previous,
        public readonly ?RedirectUrl              $redirectTo = null,
    )
    {
        parent::__construct($previous?->getMessage() ?? $this->error->name, 0, $previous);
    }

    /**
     * @param RequestErrorCodeInterface $error
     * @param RedirectUrl $redirectTo
     * @return self
     */
    public static function forRedirect(RequestErrorCodeInterface $error, RedirectUrl $redirectTo): self
    {
        return new self($error, null, $redirectTo);
    }
}