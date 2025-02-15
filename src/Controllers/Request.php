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

namespace Charcoal\Http\Router\Controllers;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Headers;
use Charcoal\Http\Commons\HttpMethod;
use Charcoal\Http\Commons\ReadOnlyPayload;
use Charcoal\Http\Commons\UrlInfo;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class Request
 * @package Charcoal\Http\Router\Controllers
 */
class Request
{
    /** @var string|null */
    public readonly ?string $contentType;

    use NoDumpTrait;
    use NotSerializableTrait;
    use NotCloneableTrait;

    /**
     * @param \Charcoal\Http\Commons\HttpMethod $method
     * @param \Charcoal\Http\Commons\UrlInfo $url
     * @param \Charcoal\Http\Commons\Headers $headers
     * @param \Charcoal\Http\Commons\ReadOnlyPayload $payload
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
