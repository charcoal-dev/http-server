<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Controllers;

use Charcoal\Contracts\Sapi\DomainMessageEnumInterface;
use Charcoal\Http\Server\Request\Controller\GatewayFacade;

/**
 * Represents a translated validation exception that includes a specific error type,
 * an optional parameter, a custom message, and an error code. This exception is
 * typically used to handle validation errors with localized or enumerated error messages.
 * @api
 */
class ValidationErrorException extends ValidationException
{
    public readonly string $translatedMessage;
    public readonly int|string $translatedCode;

    public function __construct(
        public readonly DomainMessageEnumInterface $error,
        ?array                                     $context = [],
        ?string                                    $message = null,
        int                                        $code = 0
    )
    {
        parent::__construct($message ?? $error->name, $code, $context);
    }

    /**
     * Sets the context message by translating the error message using the provided request context.
     * @internal
     */
    public function setContextMessage(GatewayFacade $context): void
    {
        $this->translatedMessage = $this->error->getTranslatedMessage($context, $this->context);
        $this->translatedCode = $this->error->getCode($context, $this->context);
    }
}