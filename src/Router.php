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

namespace Charcoal\Http\Router;

use Charcoal\Http\Router\Controllers\AbstractController;
use Charcoal\Http\Router\Controllers\Request;
use Charcoal\Http\Router\Exception\RouterException;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class Router
 * @package Charcoal\Http\Router
 */
class Router
{
    /** @var array */
    private array $routes = [];
    /** @var int */
    private int $count = 0;
    /** @var null|string */
    private ?string $fallbackController = null;
    /** @var array */
    private array $controllersArgs = [];

    use NotCloneableTrait;
    use NotSerializableTrait;
    use NoDumpTrait;

    /**
     * Router constructor.
     */
    public function __construct()
    {
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
     * Total number of routes configured in this router
     * @return int
     */
    public function routesCount(): int
    {
        return $this->count;
    }

    /**
     * Gets all defined routes in array
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
    public function fallbackController(string $controller): self
    {
        if (!class_exists($controller)) {
            throw new \InvalidArgumentException('Default router fallback controller class is invalid or does not exist');
        }

        $this->fallbackController = $controller;
        return $this;
    }

    /**
     * Defines a route, use "*" as wildcard character. A trailing "*" indicates path is to a namespace rather than a class
     * @param string $path
     * @param string $controllerClassOrNamespace
     * @return \Charcoal\Http\Router\Route
     */
    public function route(string $path, string $controllerClassOrNamespace): Route
    {
        $route = new Route($this, $path, $controllerClassOrNamespace);
        $this->routes[] = $route;
        $this->count++;
        return $route;
    }

    /**
     * Try to route request to one of the routes,
     * on fail routes request to fallback controller (if defined) or throws RouterException
     * @param \Charcoal\Http\Router\Controllers\Request $request
     * @param bool $bypassHttpAuth
     * @return \Charcoal\Http\Router\Controllers\AbstractController
     * @throws \Charcoal\Http\Router\Exception\RouterException
     */
    public function try(Request $request, bool $bypassHttpAuth = false): AbstractController
    {
        // Find controller
        $controller = null;
        /** @var Route $route */
        foreach ($this->routes as $route) {
            $controller = $route->try($request, $bypassHttpAuth);
            if ($controller) {
                break;
            }
        }

        $controller = $controller ?? $this->fallbackController;
        if (!$controller) {
            throw new RouterException('Could not route request to any controller');
        }

        return $this->createControllerInstance($controller, $request);
    }

    /**
     * @param string $controllerClass
     * @param \Charcoal\Http\Router\Controllers\Request $request
     * @param \Charcoal\Http\Router\Controllers\AbstractController|null $previous
     * @param string|null $entryPoint
     * @return \Charcoal\Http\Router\Controllers\AbstractController
     */
    public function createControllerInstance(
        string              $controllerClass,
        Request             $request,
        ?AbstractController $previous = null,
        ?string             $entryPoint = null
    ): AbstractController
    {
        try {
            $reflect = new \ReflectionClass($controllerClass);
            if (!$reflect->isSubclassOf(AbstractController::class)) {
                throw new \DomainException('Controller class does not extend "Charcoal\Http\Router\Controllers\AbstractController"');
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Could not get reflection instance for controller class', previous: $e);
        }

        return new $controllerClass($this, $request, $previous, $entryPoint, $this->controllersArgs);
    }
}