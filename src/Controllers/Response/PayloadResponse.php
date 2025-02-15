<?php
declare(strict_types=1);

namespace Charcoal\HTTP\Router\Controllers\Response;

use Charcoal\HTTP\Commons\WritablePayload;

/**
 * Class PayloadResponse
 * @package Charcoal\HTTP\Router\Controllers\Response
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
    protected function sendBody(): void
    {
        if ($this->contentType === "application/json") {
            print(json_encode($this->payload->toArray()));
        }

        throw new \RuntimeException("Cannot handle content type: " . $this->contentType);
    }
}