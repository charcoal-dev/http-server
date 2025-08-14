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
use Charcoal\Http\Commons\Header\Headers;
use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Exception\ResponseDispatchedException;

/**
 * Class AbstractResponse
 * @package Charcoal\Http\Router\Response
 */
abstract class AbstractResponse
{
    public readonly int $createdOn;
    protected WritableHeaders $headers;
    protected ?string $integrityTag = null;

    use ControlledSerializableTrait;

    public function __construct(
        protected WritableHeaders $writableHeaders,
        protected int             $statusCode = 200,
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
            "headers" => $this->headers
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
    }

    /**
     * Integrity Tag is an optional arbitrary value that uniquely represents a complete response object.
     * Its primary use is in cached responses, allowing the server to determine whether a cached response
     * is still valid for serving.
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
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setHeader(string $key, string $value): static
    {
        $this->headers->set($key, $value);
        return $this;
    }

    /**
     * @return void
     */
    abstract protected function beforeSendResponseHook(): void;

    /**
     * @return void
     */
    abstract protected function sendBody(): void;

    /**
     * @return never
     * @throws ResponseDispatchedException
     */
    public function send(): never
    {
        $this->beforeSendResponseHook();

        // HTTP Response Code
        http_response_code($this->statusCode);

        // Headers
        if ($this->headers->count()) {
            foreach ($this->headers->getArray() as $key => $val) {
                header(sprintf("%s: %s", $key, $val));
            }
        }

        $this->sendBody();

        throw new ResponseDispatchedException();
    }
}