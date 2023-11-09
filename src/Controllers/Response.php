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
use Charcoal\HTTP\Commons\WritableHeaders;
use Charcoal\HTTP\Commons\WritablePayload;

/**
 * Class Response
 * @package Charcoal\HTTP\Router\Controllers
 */
class Response
{
    private int $statusCode = 200;
    private ?string $downloadFilepath = null;

    /**
     * @param \Charcoal\HTTP\Commons\WritableHeaders $headers
     * @param \Charcoal\HTTP\Commons\WritablePayload $payload
     * @param \Charcoal\Buffers\Buffer $body
     */
    public function __construct(
        public readonly WritableHeaders $headers = new WritableHeaders(),
        public readonly WritablePayload $payload = new WritablePayload(),
        public readonly Buffer          $body = new Buffer(),
    )
    {
    }

    /**
     * @return void
     */
    public function download(): void
    {
        ob_end_clean();
        flush();
        readfile($this->downloadFilepath);
    }

    /**
     * @param string $filepath
     * @return void
     */
    public function setDownloadFilepath(string $filepath): void
    {
        $this->downloadFilepath = $filepath;
    }

    /**
     * @param string $key
     * @param string|int|float|bool|array|null $value
     * @return $this
     */
    public function set(string $key, string|int|float|bool|null|array|object $value): self
    {
        $this->payload->set($key, $value);
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function header(string $key, string $value): self
    {
        $this->headers->set($key, $value);
        return $this;
    }

    /**
     * @param int $code
     * @return $this
     */
    public function setHttpCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        return $this->statusCode;
    }
}
