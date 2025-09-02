<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Server\Contracts\RequestErrorCodeInterface;

/**
 * This class is designed to encapsulate detailed error information
 * and the associated exception that caused the failure.
 */
final readonly class ErrorResult extends AbstractResult
{
    public function __construct(
        Headers                          $headers,
        public RequestErrorCodeInterface $error,
        public ?\Throwable               $exception,
    )
    {
        $headers->set("Connection", "close");
        parent::__construct($this->error->getStatusCode(), $headers);
    }
}