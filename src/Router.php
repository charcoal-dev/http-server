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

use Charcoal\HTTP\Router\Controllers\AbstractController;
use Charcoal\HTTP\Router\Controllers\Request;
use Charcoal\HTTP\Router\Exception\RouterException;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class Router
 * @package Charcoal\HTTP\Router
 */
class Router
{
    /** @var array */
    private array $routes = [];
    /** @var int */
    private int $count = 0;
    /** @var null|string */
    private ?string $fallbackController = null;
    /** @var ResponseHandler */
    public readonly ResponseHandler $response;

    use NotCloneableTrait;
    use NotSerializableTrait;
    use NoDumpTrait;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->response = new ResponseHandler();
    }

    /**
     * @return int
     */
    public function routesCount(): int
    {
        return $this->count;
    }

    /**
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
     * @param string $path
     * @param string $controllerClassOrNamespace
     * @return \Charcoal\HTTP\Router\Route
     */
    public function route(string $path, string $controllerClassOrNamespace): Route
    {
        $route = new Route($this, $path, $controllerClassOrNamespace);
        $this->routes[] = $route;
        $this->count++;
        return $route;
    }

    /**
     * @param \Charcoal\HTTP\Router\Controllers\Request $request
     * @param bool $bypassHttpAuth
     * @return \Charcoal\HTTP\Router\Controllers\AbstractController
     * @throws \Charcoal\HTTP\Router\Exception\RouterException
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

        try {
            $reflect = new \ReflectionClass($controller);
            if (!$reflect->isSubclassOf(AbstractController::class)) {
                throw new \DomainException('Controller class does not extend "Charcoal\HTTP\Router\Controllers\AbstractController"');
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Could not get reflection instance for controller class', previous: $e);
        }

        return new $controller($this, $request);
    }
}