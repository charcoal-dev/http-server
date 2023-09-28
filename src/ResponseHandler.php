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

namespace Charcoal\HTTP\Router;

use Charcoal\HTTP\Router\Controllers\Response;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class ResponseHandler
 * @package Charcoal\HTTP\Router
 */
class ResponseHandler
{
    /** @var array */
    private array $handlers;
    /** @var \Closure */
    private \Closure $default;

    use NotCloneableTrait;
    use NotSerializableTrait;
    use NoDumpTrait;

    public function __construct()
    {
        $this->default(function (Response $response) {
            if ($response->body->len()) {
                return print $response->body->raw();
            }

            return print print_r($response->payload->toArray(), true);
        });

        // Default handlers
        $this->handle("application/json", function (Response $response) {
            return print json_encode($response->payload->toArray());
        });
    }

    /**
     * @param \Closure $handler
     * @return ResponseHandler
     */
    public function default(\Closure $handler): self
    {
        $this->default = $handler;
        return $this;
    }

    /**
     * @param string $contentType
     * @param \Closure $handler
     * @return $this
     */
    public function handle(string $contentType, \Closure $handler): self
    {
        if (!preg_match('/^\w+\/\w+$/i', $contentType)) {
            throw new \InvalidArgumentException('Invalid content type argument');
        }

        $this->handlers[strtolower($contentType)] = $handler;
        return $this;
    }

    /**
     * @param \Charcoal\HTTP\Router\Controllers\Response $response
     * @return void
     * @throws \Charcoal\HTTP\Router\Exception\RouterException
     */
    public function send(Response $response): void
    {
        // Set HTTP response Code
        if ($response->getHttpStatusCode()) {
            http_response_code($response->getHttpStatusCode());
        }

        // Headers
        if ($response->headers->count()) {
            foreach ($response->headers->toArray() as $key => $val) {
                header(sprintf('%s: %s', $key, $val));
            }
        }

        // Body
        $contentHandler = $this->default;
        $contentType = $response->headers->get("content-type");
        if ($contentType) {
            $contentType = strtolower(trim(explode(";", $contentType)[0]));
            $contentHandler = $this->handlers[$contentType] ?? $contentHandler;
        }

        call_user_func($contentHandler, $response);
    }
}