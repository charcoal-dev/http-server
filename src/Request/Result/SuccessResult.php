<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request\Result;

use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Headers\Headers;

readonly class SuccessResult extends AbstractResult
{
    public function __construct(
        int             $statusCode,
        Headers         $headers,
        WritablePayload $payload,
    )
    {
        parent::__construct($statusCode, $headers);
    }
}