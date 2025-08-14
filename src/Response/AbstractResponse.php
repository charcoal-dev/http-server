<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Response;

use Charcoal\Base\Enums\ValidationState;
use Charcoal\Base\Support\Data\CheckedKeyValue;
use Charcoal\Base\Traits\ControlledSerializableTrait;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Header\Headers;
use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Controller\FinalizedResponse;

/**
 * Class AbstractResponse
 * @package Charcoal\Http\Router\Response
 */
abstract class AbstractResponse
{
    public readonly int $createdOn;
    protected ?string $integrityTag = null;

    use ControlledSerializableTrait;

    /**
     * @param WritableHeaders $headers
     * @param ContentType|null $contentType
     * @param int $statusCode
     */
    public function __construct(
        public WritableHeaders $headers,
        public ?ContentType    $contentType,
        protected int          $statusCode = 200,
    )
    {
        $this->createdOn = time();
    }

    /**
     * @return class-string[]
     */
    public static function unserializeDependencies(): array
    {
        return [
            static::class,
            ValidationState::class,
            CheckedKeyValue::class,
            Headers::class,
            WritableHeaders::class,
        ];
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        return [
            "createdOn" => $this->createdOn,
            "statusCode" => $this->statusCode,
            "integrityTag" => $this->integrityTag,
            "headers" => $this->headers,
            "contentType" => $this->contentType,
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->createdOn = $data["createdOn"];
        $this->integrityTag = $data["integrityTag"];
        $this->statusCode = $data["statusCode"];
        $this->headers = $data["headers"];
        $this->contentType = $data["contentType"];
    }

    /**
     * @param string $tag
     * @return void
     */
    public function setIntegrityTag(string $tag): void
    {
        $this->integrityTag = $tag;
    }

    /**
     * @return string|null
     */
    public function getIntegrityTag(): ?string
    {
        return $this->integrityTag;
    }

    /**
     * @param int $code
     * @return $this
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string|Buffer|null
     */
    abstract protected function getBody(): null|string|Buffer;

    /**
     * @return FinalizedResponse
     */
    public function finalize(): FinalizedResponse
    {
        return new FinalizedResponse($this->statusCode, $this->headers, $this->contentType, $this->getBody());
    }
}