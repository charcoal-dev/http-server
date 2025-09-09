<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Internal\Response;

use Charcoal\Http\Commons\Contracts\ContentTypeEnumInterface;
use Charcoal\Http\Server\Request\Result\Success\FileDownloadResponse;

/**
 * Interrupt during controller execution to trigger a file download.
 */
final class FileDownloadInterrupt extends ResponseFinalizedInterrupt
{
    public readonly string $filepath;

    /** @internal */
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

    /** @internal */
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