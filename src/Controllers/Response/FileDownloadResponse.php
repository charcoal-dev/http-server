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

/**
 * Class FileDownloadResponse
 * @package Charcoal\Http\Router\Controllers\Response
 */
class FileDownloadResponse extends AbstractControllerResponse
{
    public readonly string $filename;

    /**
     * @param string $filepath
     * @param string $contentType
     * @param string|null $filename
     * @param string $encoding
     * @param string $pragma
     * @param string $expires
     */
    public function __construct(
        public readonly string $filepath,
        public readonly string $contentType,
        ?string                $filename = null,
        public readonly string $encoding = "binary",
        public readonly string $pragma = "no-cache",
        public readonly string $expires = "0"
    )
    {
        parent::__construct();
        $this->filename = $filename ?? basename($filepath);
    }

    /**
     * @return void
     */
    protected function beforeSendResponseHook(): void
    {
        $this->headers->set("Content-type", $this->contentType)
            ->set("Content-Disposition", "attachment; filename=" . $this->filename)
            ->set("Content-Transfer-Encoding", $this->encoding)
            ->set("Pragma", $this->pragma)
            ->set("Expires", $this->expires);
    }

    /**
     * @return void
     */
    protected function sendBody(): void
    {
        if (ob_get_level() > 0) {
            throw new \RuntimeException("Cannot send file download response with output buffering enabled");
        }

        readfile($this->filepath);
    }
}