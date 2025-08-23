<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Controllers\Promise;

use Charcoal\Base\Traits\ControlledSerializableTrait;
use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Contracts\Response\ResponsePromisedOnDispatch;

/**
 * Class FileDownload
 * @package Charcoal\Http\Router\Controller\Promise
 */
class FileDownload implements ResponsePromisedOnDispatch
{
    public readonly string $filename;

    use ControlledSerializableTrait;

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
        $this->filename = $filename ?? basename($filepath);
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        $data["filepath"] = $this->filepath;
        $data["filename"] = $this->filename;
        $data["contentType"] = $this->contentType;
        $data["encoding"] = $this->encoding;
        $data["pragma"] = $this->pragma;
        $data["expires"] = $this->expires;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->filepath = $data["filepath"];
        $this->filename = $data["filename"];
        $this->contentType = $data["contentType"];
        $this->encoding = $data["encoding"];
        $this->pragma = $data["pragma"];
        $this->expires = $data["expires"];
    }

    /**
     * @param WritableHeaders $headers
     * @return void
     */
    public function setHeaders(WritableHeaders $headers): void
    {
        $headers->set("Content-type", $this->contentType)
            ->set("Content-Disposition", "attachment; filename=" . $this->filename)
            ->set("Content-Transfer-Encoding", $this->encoding)
            ->set("Pragma", $this->pragma)
            ->set("Expires", $this->expires);
    }

    /**
     * @return void
     */
    public function resolve(): void
    {
        if (ob_get_level() > 0) {
            throw new \RuntimeException("Cannot send file download response with output buffering enabled");
        }

        readfile($this->filepath);
    }
}