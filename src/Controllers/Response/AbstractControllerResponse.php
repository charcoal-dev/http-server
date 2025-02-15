<?php
declare(strict_types=1);

namespace Charcoal\HTTP\Router\Controllers\Response;

use Charcoal\HTTP\Commons\WritableHeaders;
use Charcoal\HTTP\Router\Exception\ResponseDispatchedException;

/**
 * Class AbstractControllerResponse
 * @package Charcoal\HTTP\Router\Controllers\Response
 */
abstract class AbstractControllerResponse
{
    public function __construct(
        protected int          $statusCode = 200,
        public WritableHeaders $headers = new WritableHeaders()
    )
    {
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
            foreach ($this->headers->toArray() as $key => $val) {
                header(sprintf('%s: %s', $key, $val));
            }
        }

        $this->sendBody();

        throw new ResponseDispatchedException();
    }
}