<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Controller;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Header\Headers;
use Charcoal\Http\Router\Contracts\Response\ResponseResolvedInterface;

/**
 * Class FinalizedResponse
 * @package Charcoal\Http\Router\Controller
 */
readonly class FinalizedResponse implements ResponseResolvedInterface
{
    public function __construct(
        public int                $statusCode,
        public Headers            $headers,
        public ?ContentType       $contentType,
        public null|string|Buffer $body,
    )
    {
    }
}
