<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Controller;

use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Enums\ContentType;

/**
 * A facade class for managing response-related functionality.
 * Extends the UnsafePayload to leverage its capabilities while
 * providing specific methods for response handling.
 */
final class ResponseFacade extends WritablePayload
{
    private ContentType $contentType = ContentType::Json;
    private int $responseCode = 200;
    private bool $isCacheable = true;

    /** @api */
    public function setStatusCode(int $statusCode): self
    {
        $this->responseCode = $statusCode;
        return $this;
    }

    /** @api */
    public function setContentType(ContentType $contentType): self
    {
        $this->contentType = $contentType;
        return $this;
    }

    /** @api */
    public function setCacheable(bool $isCacheable): self
    {
        $this->isCacheable = $isCacheable;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->responseCode;
    }

    /**
     * @return ContentType
     */
    public function getContentType(): ContentType
    {
        return $this->contentType;
    }

    /**
     * @return bool
     */
    public function isCacheable(): bool
    {
        return $this->isCacheable;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function useDto(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->set((string)$key, $value);
        }

        return $this;
    }
}