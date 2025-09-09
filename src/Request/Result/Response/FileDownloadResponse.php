<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Result\Response;

use Charcoal\Base\Support\ErrorHelper;
use Charcoal\Http\Commons\Contracts\ContentTypeEnumInterface;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Server\Contracts\Request\SuccessResponseInterface;
use Charcoal\Http\Server\Exceptions\Request\ResponseBytesDispatchedException;

/**
 * Represents a response for a file download operation, implementing the SuccessResponseInterface.
 * This class is immutable and facilitates the handling of download responses.
 */
final readonly class FileDownloadResponse implements SuccessResponseInterface
{
    public int $filesize;

    public function __construct(
        public int                      $statusCode,
        public string                   $filepath,
        public string                   $downloadFilename,
        public ContentTypeEnumInterface $contentType,
        ?int                            $filesize = null
    )
    {
        if (!$this->downloadFilename) {
            throw new \RuntimeException("Invalid download filename");
        }

        error_clear_last();
        if (!@file_exists($filepath) || !@is_file($filepath)) {
            throw new \RuntimeException("File does not exist: " . $filepath,
                previous: ErrorHelper::lastErrorToRuntimeException());
        } elseif (!@is_readable($filepath)) {
            throw new \RuntimeException("File is not readable: " . $filepath,
                previous: ErrorHelper::lastErrorToRuntimeException());
        }

        $filesize = $filesize ?? @filesize($filepath);
        if (!$filesize || $filesize <= 0) {
            throw new \RuntimeException("Filesize could not be resolved: " . $filepath,
                previous: ErrorHelper::lastErrorToRuntimeException());
        }

        $this->filesize = $filesize;
    }

    /**
     * @return bool
     */
    public function isCacheable(): bool
    {
        return false;
    }

    /**
     * @param Headers $headers
     * @return void
     */
    public function setHeaders(Headers $headers): void
    {
        $headers->set("Content-Disposition", "attachment; filename=\"" . $this->downloadFilename . "\"");
        $headers->set("Content-Type", (string)$this->contentType->value);
        $headers->set("Content-Length", (string)$this->filesize);
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return never
     * @throws ResponseBytesDispatchedException
     */
    public function send(): never
    {
        if (false === readfile($this->filepath)) {
            throw new \RuntimeException("File could not be read: " . $this->filepath);
        }

        throw new ResponseBytesDispatchedException();
    }
}