<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Controllers;

use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Router\Contracts\Auth\AuthContextInterface;
use Charcoal\Http\Router\Contracts\Response\ResponseResolvedInterface;
use Charcoal\Http\Router\Controllers\Promise\FileDownload;
use Charcoal\Http\Router\Exceptions\ControllerException;
use Charcoal\Http\Router\Request\ServerRequest;
use Charcoal\Http\Router\Response\AbstractResponse;
use Charcoal\Http\Router\Response\CacheControl;
use Charcoal\Http\Router\Routing\Route;

/**
 * Class AbstractController
 * @package Charcoal\Http\Router\Controllers
 */
abstract class AbstractControllerOld
{
    private ?AbstractResponse $response;
    private ?CacheControl $cacheControl = null;
    public readonly ?AuthContextInterface $authContext2;

    use NoDumpTrait;
    use NotCloneableTrait;
    use NotSerializableTrait;

    /**
     * @param Route $route
     * @param ServerRequest $request
     * @param AbstractControllerOld|null $previous
     * @param string|null $entryPoint
     * @param array $constructorArgs
     * @throws ControllerException
     */
    public function __construct(
        public readonly Route         $route,
        public readonly ServerRequest $request,
        ?AbstractControllerOld        $previous = null,
        protected ?string             $entryPoint = null,
        array                         $constructorArgs = [],
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

        $this->route->isProtected()?->authenticate($this->request);

        $this->resolveEntryPoint($constructorArgs);
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
    abstract protected function resolveEntryPoint(array $args): void;

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
     * @api
     */
    public function swapResponseObject(AbstractResponse $response): void
    {
        $this->response = $response;
    }

    /**
     * @return UnsafePayload
     * @api
     */
    public function payload(): UnsafePayload
    {
        return $this->request->payload;
    }

    /**
     * @param ResponseResolvedInterface|null $response
     * @return void
     */
    abstract protected function responseDispatcherHook(?ResponseResolvedInterface $response): void;

    /**
     * @return never
     * @throws \Charcoal\Http\Router\Exceptions\ResponseDispatchedException
     * @api
     */
    public function send(): never
    {
        if ($this->cacheControl) {
            $this->response->headers->set("Cache-Control", $this->cacheControl->getHeaderValue());
        }

        $this->response->responseDispatcherHook();
        $finalized = $this->response->finalize();
        $this->responseDispatcherHook($finalized);
        ResponseDispatcher::dispatch($finalized);
    }

    /**
     * @param int $statusCode
     * @param FileDownload $file
     * @param CacheControl|null $cacheControl
     * @return never
     * @throws \Charcoal\Http\Router\Exceptions\ResponseDispatchedException
     * @api
     */
    public function sendFileDownload(int $statusCode, FileDownload $file, ?CacheControl $cacheControl): never
    {
        if ($cacheControl) {
            $this->response->headers->set("Cache-Control", $this->cacheControl->getHeaderValue());
        }

        $this->response->responseDispatcherHook();
        $this->responseDispatcherHook($file);
        ResponseDispatcher::dispatchPromise($statusCode, $this->response->headers, $file);
    }

    /**
     * @param int $statusCode
     * @return never
     * @throws \Charcoal\Http\Router\Exceptions\ResponseDispatchedException
     * @api
     */
    public function terminate(int $statusCode): never
    {
        $this->response->responseDispatcherHook();
        $this->responseDispatcherHook(null);
        ResponseDispatcher::dispatch(new FinalizedResponse($statusCode, $this->response->headers, null, null));
    }

    /**
     * @param AuthContextInterface $context
     * @return void
     * @api
     */
    public function setAuthorized(AuthContextInterface $context): void
    {
        if (isset($this->authContext2)) {
            throw new \LogicException("Authorization context already set");
        }

        $this->authContext2 = $context;
    }

    /**
     * @param string $controllerClass
     * @param string $entryPoint
     * @return AbstractControllerOld
     * @api
     */
    public function forwardToController(string $controllerClass, string $entryPoint): AbstractControllerOld
    {
        return $this->route->router->createControllerInstance($controllerClass, $this->request, $this, $entryPoint);
    }

    /**
     * @param string $url
     * @param int|null $code
     * @api
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
