<?php
/*
 * This file is a part of "charcoal-dev/http-router" package.
 * https://github.com/charcoal-dev/http-router
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/http-router/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\HTTP\Router\Controllers;

use Charcoal\Buffers\Buffer;
use Charcoal\HTTP\Commons\Headers;
use Charcoal\HTTP\Commons\HttpMethod;
use Charcoal\HTTP\Commons\ReadOnlyPayload;
use Charcoal\HTTP\Commons\UrlInfo;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class Request
 * @package Charcoal\HTTP\Router\Controllers
 */
class Request
{
    /** @var string|null */
    public readonly ?string $contentType;

    use NoDumpTrait;
    use NotSerializableTrait;
    use NotCloneableTrait;

    /**
     * @param \Charcoal\HTTP\Commons\HttpMethod $method
     * @param \Charcoal\HTTP\Commons\UrlInfo $url
     * @param \Charcoal\HTTP\Commons\Headers $headers
     * @param \Charcoal\HTTP\Commons\ReadOnlyPayload $payload
     * @param \Charcoal\Buffers\Buffer $body
     */
    public function __construct(
        public readonly HttpMethod      $method,
        public readonly UrlInfo         $url,
        public readonly Headers         $headers,
        public readonly ReadOnlyPayload $payload,
        public readonly Buffer          $body
    )
    {
        $this->contentType = $this->headers->has("content-type") ?
            trim(explode(";", $this->headers->get("content-type"))[0]) : null;
    }
}
