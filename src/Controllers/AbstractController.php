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

namespace Charcoal\Http\Router\Controllers;

use Charcoal\Http\Commons\ReadOnlyPayload;
use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;
use Charcoal\Http\Router\Exception\ControllerException;
use Charcoal\Http\Router\Router;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class AbstractController
 * @package Charcoal\Http\Router\Controllers
 */
abstract class AbstractController
{
    private AbstractControllerResponse $response;
    private ?CacheControl $cacheControl = null;

    use NoDumpTrait;
    use NotCloneableTrait;
    use NotSerializableTrait;

    /**
     * @param \Charcoal\Http\Router\Router $router
     * @param \Charcoal\Http\Router\Controllers\Request $request
     * @param \Charcoal\Http\Router\Controllers\AbstractController|null $prev
     * @param string|null $entryPoint
     * @param array $constructorArgs
     * @throws \Charcoal\Http\Router\Exception\ControllerException
     */
    public function __construct(
        public readonly Router  $router,
        public readonly Request $request,
        ?AbstractController     $prev = null,
        protected ?string       $entryPoint = null,
        array                   $constructorArgs = []
    )
    {
        $this->response = $prev?->getResponseObject() ?? $this->initEmptyResponse();

        if ($entryPoint) {
            $this->entryPoint = method_exists($this, $entryPoint) ? $entryPoint : null;
            if (!$this->entryPoint) {
                throw new ControllerException(
                    sprintf('Entrypoint method "%s" does not exist in controller class "%s"', $entryPoint, static::class)
                );
            }
        }

        $this->onConstructHook($constructorArgs);
    }

    /**
     * @param CacheControl $cacheControl
     * @return void
     */
    protected function useCacheControl(CacheControl $cacheControl): void
    {
        $this->cacheControl = $cacheControl;
    }

    /**
     * @return void
     */
    protected function unsetCacheControl(): void
    {
        $this->cacheControl = null;
    }

    /**
     * @param array $args
     * @return void
     */
    abstract protected function onConstructHook(array $args): void;

    /**
     * @return AbstractControllerResponse
     */
    abstract protected function initEmptyResponse(): AbstractControllerResponse;

    /**
     * @return AbstractControllerResponse
     */
    public function getResponseObject(): AbstractControllerResponse
    {
        return $this->response;
    }

    /**
     * @param AbstractControllerResponse $response
     * @return void
     */
    public function swapResponseObject(AbstractControllerResponse $response): void
    {
        $this->response = $response;
    }

    /**
     * @return \Charcoal\Http\Commons\ReadOnlyPayload
     */
    public function input(): ReadOnlyPayload
    {
        return $this->request->payload;
    }

    /**
     * @return never
     * @throws \Charcoal\Http\Router\Exception\ResponseDispatchedException
     */
    public function sendResponse(): never
    {
        if ($this->cacheControl) {
            $this->response->headers->set("Cache-Control", $this->cacheControl->getHeaderValue());
        }

        $this->response->send();
    }

    /**
     * @param string $controllerClass
     * @param string $entryPoint
     * @return \Charcoal\Http\Router\Controllers\AbstractController
     */
    public function forwardToController(string $controllerClass, string $entryPoint): AbstractController
    {
        return $this->router->createControllerInstance($controllerClass, $this->request, $this, $entryPoint);
    }

    /**
     * @param string $url
     * @param int|null $code
     */
    public function redirectOut(string $url, ?int $code = null): never
    {
        $code = $code ?? $this->response->getStatusCode();
        if ($code > 0) {
            http_response_code($code);
        }

        header(sprintf('Location: %s', $url));
        exit;
    }
}
