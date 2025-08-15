<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router;

use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Http\Router\Contracts\RoutingInterface;
use Charcoal\Http\Router\Controller\AbstractController;
use Charcoal\Http\Router\Exceptions\RoutingException;
use Charcoal\Http\Router\Policy\RouterPolicy;
use Charcoal\Http\Router\Request\Request;

/**
 * Class Router
 * @package Charcoal\Http\Router
 */
class Router implements RoutingInterface
{
    /** @var array<Route> */
    private array $routes = [];
    private int $count = 0;


    /** @var null|string */
    private ?string $fallbackController = null;
    /** @var array */
    private array $controllersArgs = [];

    use NoDumpTrait;
    use NotCloneableTrait;
    use NotSerializableTrait;

    public function __construct(
        public readonly RouterPolicy $policy
    )
    {
    }

    public function routerCount(): int
    {
        return $this->count;
    }

    /**
     * @param array $args
     * @return void
     */
    public function setControllersArgs(array $args): void
    {
        $this->controllersArgs = $args;
    }

    /**
     * Gets all defined routes in an array
     * @return array
     */
    public function routesArray(): array
    {
        return $this->routes;
    }

    /**
     * Fallback/default controller class, this class is invoked when a request cannot be routed to any controller
     * @param string $controller
     * @return $this
     */
    public function fallbackController(string $controller): static
    {
        if (!class_exists($controller)) {
            throw new \InvalidArgumentException('Default router fallback controller class is invalid or does not exist');
        }

        $this->fallbackController = $controller;
        return $this;
    }

    /**
     * @param string $path
     * @param string $controllerClassOrNamespace
     * @return Route
     */
    public function route(string $path, string $controllerClassOrNamespace): Route
    {
        $route = new Route($this, $path, $controllerClassOrNamespace);
        $this->routes[] = $route;
        $this->count++;
        return $route;
    }

    /**
     * @param Request $request
     * @return AbstractController
     * @throws RoutingException
     */
    public function try(Request $request): AbstractController
    {
        // Find controller
        $controller = null;
        foreach ($this->routes as $route) {
            $controller = $route->try($request);
            if ($controller) {
                break;
            }
        }

        $controller = $controller ?? $this->fallbackController;
        if (!$controller) {
            throw new RoutingException('Could not route request to any controller');
        }

        return $this->createControllerInstance($controller, $request);
    }

    /**
     * @param class-string<AbstractController> $controllerClass
     * @param Request $request
     * @param AbstractController|null $previous
     * @param string|null $entryPoint
     * @return AbstractController
     */
    public function createControllerInstance(
        string              $controllerClass,
        Request             $request,
        ?AbstractController $previous = null,
        ?string             $entryPoint = null
    ): AbstractController
    {
        if (!is_subclass_of($controllerClass, AbstractController::class, true)) {
            throw new \DomainException('Controller class does not extend "' . AbstractController::class . '"');
        }

        return new $controllerClass($this, $request, $previous, $entryPoint, $this->controllersArgs);
    }
}