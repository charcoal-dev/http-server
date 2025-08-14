<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Data\UrlInfo;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Commons\Header\Headers;
use Charcoal\Http\Router\Request\Headers\Authorization;

/**
 * Class Request
 * @package Charcoal\Http\Router\Route
 */
class Request
{
    public readonly ?string $contentType;
    private ?Authorization $authorization = null;

    use NoDumpTrait;
    use NotCloneableTrait;
    use NotSerializableTrait;

    /**
     * @param HttpMethod $method
     * @param UrlInfo $url
     * @param Headers $headers
     * @param UnsafePayload $payload
     * @param Buffer $body
     */
    public function __construct(
        public readonly HttpMethod    $method,
        public readonly UrlInfo       $url,
        public readonly Headers       $headers,
        public readonly UnsafePayload $payload,
        public readonly Buffer        $body
    )
    {
        $this->contentType = ContentType::find($this->headers->get("content-type") ?: "");
    }

    /**
     * @return Authorization
     */
    public function authorization(): Authorization
    {
        if (!$this->authorization) {
            $this->authorization = new Authorization($this->headers);
        }

        return $this->authorization;
    }
}
