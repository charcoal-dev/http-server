<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Response;

use Charcoal\Http\Commons\Header\WritableHeaders;

/**
 * Class NoContentResponse
 * @package Charcoal\Http\Router\Response
 */
class NoContentResponse extends AbstractResponse
{
    public function __construct(WritableHeaders $headers, int $statusCode)
    {
        parent::__construct($headers, null, $statusCode);
    }

    protected function getBody(): null
    {
        return null;
    }
}

