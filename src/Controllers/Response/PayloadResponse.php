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

use Charcoal\Http\Commons\WritablePayload;

/**
 * Class PayloadResponse
 * @package Charcoal\Http\Router\Controllers\Response
 */
class PayloadResponse extends AbstractControllerResponse
{
    /**
     * @param string $contentType
     * @param WritablePayload $payload
     */
    public function __construct(
        public readonly string          $contentType = "application/json",
        public readonly WritablePayload $payload = new WritablePayload()
    )
    {
        parent::__construct();
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["contentType"] = $this->contentType;
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
        $this->contentType = $data["contentType"];
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
     * @return void
     */
    protected function beforeSendResponseHook(): void
    {
        $this->headers->set("Content-type", $this->contentType);
    }

    /**
     * @return void
     */
    final protected function sendBody(): void
    {
        $this->sendBodyByContentType($this->getBodyArray());
    }

    /**
     * @return array
     */
    protected function getBodyArray(): array
    {
        return $this->payload->toArray();
    }

    /**
     * @param array $body
     * @return void
     */
    protected function sendBodyByContentType(array $body): void
    {
        if ($this->contentType === "application/json") {
            print(json_encode($body));
            return;
        }

        throw new \RuntimeException("Cannot handle content type: " . $this->contentType);
    }
}