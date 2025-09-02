<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Controllers;

use Charcoal\Http\Server\Contracts\Controllers\ValidationErrorEnumInterface;
use Charcoal\Http\Server\Request\Controller\RequestFacade;

/**
 * Represents a translated validation exception that includes a specific error type,
 * an optional parameter, a custom message, and an error code. This exception is
 * typically used to handle validation errors with localized or enumerated error messages.
 * @api
 */
class ValidationErrorException extends ValidationException
{
    public readonly string $translatedMessage;

    public function __construct(
        public readonly ValidationErrorEnumInterface $error,
        ?array                                       $context = [],
        ?string                                      $message = null,
        int                                          $code = 0
    )
    {
        parent::__construct($message ?? $error->name, $code, $context);
    }

    /**
     * Sets the context message by translating the error message using the provided request context.
     * @internal
     */
    public function setContextMessage(RequestFacade $context): void
    {
        $this->translatedMessage = $this->error->getTranslatedMessage($context);
    }
}