<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Response;

use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Header\WritableHeaders;

/**
 * Class PayloadResponse
 * @package Charcoal\Http\Router\Controllers\Response
 */
class PayloadResponse extends AbstractResponse
{
    /**
     * @param WritableHeaders $headers
     * @param WritablePayload $payload
     * @param ContentType $contentType
     * @param int $statusCode
     */
    public function __construct(
        WritableHeaders                 $headers,
        public readonly WritablePayload $payload,
        ContentType                     $contentType = ContentType::JSON,
        int                             $statusCode = 200
    )
    {
        parent::__construct($headers, $contentType, $statusCode);
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["payload"] = $this->payload;
        return $data;
    }

    /**
     * @return class-string[]
     */
    public static function unserializeDependencies(): array
    {
        return [...parent::unserializeDependencies(), WritablePayload::class];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->payload = $data["payload"];
        parent::__unserialize($data);
    }

    /**
     * @param string $key
     * @param string|int|float|bool|array|object|null $value
     * @return $this
     */
    public function set(string $key, string|int|float|bool|null|array|object $value): static
    {
        $this->payload->set($key, $value);
        return $this;
    }

    /**
     * @return string
     */
    protected function getBody(): string
    {
        return json_encode($this->payload->getArray());
    }
}