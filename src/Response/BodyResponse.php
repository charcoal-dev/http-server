<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Response;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Header\WritableHeaders;

/**
 * Class BodyResponse
 * @package Charcoal\Http\Router\Response
 */
class BodyResponse extends AbstractResponse
{
    /**
     * @param WritableHeaders $headers
     * @param ContentType $contentType
     * @param Buffer $body
     * @param int $statusCode
     */
    public function __construct(
        WritableHeaders        $headers,
        ContentType            $contentType = ContentType::HTML,
        public readonly Buffer $body = new Buffer(),
        int                    $statusCode = 200
    )
    {
        parent::__construct($headers, $contentType, $statusCode);
    }

    /**
     * @return class-string[]
     */
    public static function unserializeDependencies(): array
    {
        return [...parent::unserializeDependencies(), Buffer::class];
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["body"] = $this->body;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->body = $data["body"];
        parent::__unserialize($data);
    }

    /**
     * @return Buffer|null
     */
    protected function getBody(): ?Buffer
    {
        if (!$this->body->len()) {
            throw new \RuntimeException("No response body set");
        }

        return $this->body;
    }
}