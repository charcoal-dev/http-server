<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Controller;

use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Router\Controller\Promise\FileDownload;
use Charcoal\Http\Router\Exception\ControllerException;
use Charcoal\Http\Router\Request\Request;
use Charcoal\Http\Router\Response\AbstractResponse;
use Charcoal\Http\Router\Response\Headers\CacheControl;
use Charcoal\Http\Router\Router;

/**
 * Class AbstractController
 * @package Charcoal\Http\Router\Controllers
 */
abstract class AbstractController
{
    private AbstractResponse $response;
    private ?CacheControl $cacheControl = null;

    use NoDumpTrait;
    use NotCloneableTrait;
    use NotSerializableTrait;

    /**
     * @param Router $router
     * @param Request $request
     * @param AbstractController|null $previous
     * @param string|null $entryPoint
     * @param array $constructorArgs
     * @throws ControllerException
     */
    public function __construct(
        public readonly Router  $router,
        public readonly Request $request,
        ?AbstractController     $previous = null,
        protected ?string       $entryPoint = null,
        array                   $constructorArgs = []
    )
    {
        $this->response = $previous?->getResponseObject() ?? $this->createResponseObject();
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
     * @api
     */
    protected function setCacheControl(CacheControl $cacheControl): void
    {
        $this->cacheControl = $cacheControl;
    }

    /**
     * @return void
     * @api
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
     * @return AbstractResponse
     */
    abstract protected function createResponseObject(): AbstractResponse;

    /**
     * @return AbstractResponse
     */
    public function getResponseObject(): AbstractResponse
    {
        return $this->response;
    }

    /**
     * @param AbstractResponse $response
     * @return void
     */
    public function swapResponseObject(AbstractResponse $response): void
    {
        $this->response = $response;
    }

    /**
     * @return UnsafePayload
     */
    public function payload(): UnsafePayload
    {
        return $this->request->payload;
    }

    /**
     * @return never
     * @throws \Charcoal\Http\Router\Exception\ResponseDispatchedException
     */
    public function send(): never
    {
        if ($this->cacheControl) {
            $this->response->headers->set("Cache-Control", $this->cacheControl->getHeaderValue());
        }

        ResponseDispatcher::dispatch($this->response->finalize());
    }

    /**
     * @param int $statusCode
     * @param FileDownload $file
     * @param CacheControl|null $cacheControl
     * @return never
     * @throws \Charcoal\Http\Router\Exception\ResponseDispatchedException
     */
    public function sendFileDownload(int $statusCode, FileDownload $file, ?CacheControl $cacheControl): never
    {
        if ($cacheControl) {
            $this->response->headers->set("Cache-Control", $this->cacheControl->getHeaderValue());
        }

        ResponseDispatcher::dispatchPromise($statusCode, $this->response->headers, $file);
    }

    /**
     * @param string $controllerClass
     * @param string $entryPoint
     * @return AbstractController
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
