<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result;

use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Headers\Headers;

/**
 * Represents a successful result of an operation with associated HTTP status code,
 * headers, and payload. This class is immutable and extends the AbstractResult base class.
 */
readonly class SuccessResult extends AbstractResult
{
    public function __construct(
        int              $statusCode,
        Headers          $headers,
        ?WritablePayload $payload,
    )
    {
        // Todo: Compile final body from payload

        $headers->set("Content-Length", (string)strlen($this->body ?? ""));
        parent::__construct($statusCode, $headers, $payload);
    }
}