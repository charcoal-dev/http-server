<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Controllers;

use Charcoal\Http\Commons\Contracts\ContentTypeEnumInterface;
use Charcoal\Http\Server\Request\Result\Success\FileDownloadResponse;

/**
 * Interrupt during controller execution to trigger a file download.
 */
final class FileDownloadException extends ResponseFinalizedException
{
    public readonly string $filepath;

    public function __construct(
        string                                   $filepath,
        public readonly string                   $downloadFilename,
        public readonly ContentTypeEnumInterface $contentType,
        int                                      $statusCode = 200,
        public readonly ?int                     $filesize = null
    )
    {
        $this->filepath = realpath($filepath);
        parent::__construct($statusCode);
    }

    public function getResponseObject(): FileDownloadResponse
    {
        return new FileDownloadResponse(
            $this->statusCode,
            $this->filepath,
            $this->downloadFilename,
            $this->contentType,
            $this->filesize
        );
    }
}