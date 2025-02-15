<?php
declare(strict_types=1);

namespace Charcoal\HTTP\Router\Controllers\Response;

use Charcoal\Buffers\Buffer;

/**
 * Class BodyResponse
 * @package Charcoal\HTTP\Router\Controllers\Response
 */
class BodyResponse extends AbstractControllerResponse
{
    /**
     * @param string $contentType
     * @param Buffer $body
     */
    public function __construct(
        public readonly string $contentType = "text/html",
        public readonly Buffer $body = new Buffer()
    )
    {
        parent::__construct();
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
        if (!$this->body->len()) {
            throw new \RuntimeException("No response body set");
        }

        print($this->body->raw());
    }
}