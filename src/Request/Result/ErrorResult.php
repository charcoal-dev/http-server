<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request\Result;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Router\Enums\RequestError;

/**
 * This class is designed to encapsulate detailed error information
 * and the associated exception that caused the failure.
 */
final readonly class ErrorResult extends AbstractResult
{
    public function __construct(
        Headers             $headers,
        public RequestError $error,
        public \Throwable   $exception,
    )
    {
        parent::__construct($this->error->getStatusCode(), $headers);
    }
}