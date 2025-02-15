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

namespace Charcoal\Http\Router\Controllers\Response;

use Charcoal\Buffers\Buffer;

/**
 * Class BodyResponse
 * @package Charcoal\Http\Router\Controllers\Response
 */
class BodyResponse extends AbstractControllerResponse
{
    /**
     * @param string $contentType
     * @param Buffer $body
     */
    public function __construct(
        public readonly string $contentType = "text/html",
        public readonly Buffer $body = new Buffer()
    )
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function beforeSendResponseHook(): void
    {
        $this->headers->set("Content-type", $this->contentType);
    }

    /**
     * @return void
     */
    protected function sendBody(): void
    {
        if (!$this->body->len()) {
            throw new \RuntimeException("No response body set");
        }

        print($this->body->raw());
    }
}