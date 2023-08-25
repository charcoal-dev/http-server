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

use Charcoal\HTTP\Router\Authorization\AbstractAuthorization;
use Charcoal\HTTP\Router\Controllers\Request;
use Charcoal\OOP\CaseStyles;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class Route
 * @package Charcoal\HTTP\Router
 */
class Route
{
    /** @var int */
    public readonly int $id;
    /** @var string */
    public readonly string $path;
    /** @var string */
    public readonly string $matchRegExp;
    /** @var string */
    public readonly string $controller;
    /** @var bool */
    public readonly bool $isNamespace;
    /** @var array */
    private array $ignorePathIndexes = [];
    /** @var null|string */
    private ?string $fallbackController = null;
    /** @var \Charcoal\HTTP\Router\Authorization\AbstractAuthorization|null */
    private ?AbstractAuthorization $auth = null;

    use NotCloneableTrait;
    use NotSerializableTrait;
    use NoDumpTrait;

    /**
     * @param \Charcoal\HTTP\Router\Router $router
     * @param string $path
     * @param string $namespaceOrClass
     */
    public function __construct(
        private readonly Router $router,
        string                  $path,
        string                  $namespaceOrClass
    )
    {
        $this->id = $this->router->routesCount() + 1;

        // URL Path
        $path = "/" . trim(strtolower($path), "/"); // Case-insensitivity
        if (!preg_match('/^((\/?[\w\-.]+)|(\/\*))*(\/\*)?$/', $path)) {
            throw new \InvalidArgumentException('Route URL path argument contain an illegal character', $this->id);
        }

        // Controller or Namespace
        if (!preg_match('/^\w+(\\\\\w+)*(\\\\\*)?$/i', $namespaceOrClass)) {
            throw new \InvalidArgumentException('Class or namespace contains an illegal character', $this->id);
        }

        $urlIsWildcard = str_ends_with($path, '/*');
        $controllerIsWildcard = str_ends_with($namespaceOrClass, '\*');
        if ($controllerIsWildcard && !$urlIsWildcard) {
            throw new \InvalidArgumentException('Route URL must end with "/*"', $this->id);
        }

        $this->path = $path;
        $this->matchRegExp = $this->routeRegExp();
        $this->controller = $namespaceOrClass;
        $this->isNamespace = $controllerIsWildcard;
        $this->ignorePathIndexes = [];
    }

    /**
     * Sets a fallback controller specific to this route, useful for namespace routes
     * @param string $controller
     * @return $this
     */
    public function fallbackController(string $controller): self
    {
        if (!class_exists($controller)) {
            throw new \InvalidArgumentException('Fallback controller class is invalid or does not exist', $this->id);
        }

        $this->fallbackController = $controller;
        return $this;
    }

    /**
     * @return string
     */
    private function routeRegExp(): string
    {
        // Init pattern from URL prop
        $pattern = "/^" . preg_quote($this->path, "/");

        // Last wildcard
        if (str_ends_with($pattern, "\/\*")) {
            $pattern = substr($pattern, 0, -4) . '(\/[\w\-\.]+)*';
        }

        // Optional trailing "/"
        $pattern .= "\/?";

        // Middle wildcards
        $pattern = str_replace('\*', '[^\/]?[\w\-\.]+', $pattern);

        // Finalise and return
        return $pattern . "$/";
    }

    /**
     * Following path indexes will be ignored while routing to a classname
     * @param int ...$indexes
     * @return Route
     */
    public function ignorePathIndexes(int ...$indexes): self
    {
        $this->ignorePathIndexes = $indexes;
        return $this;
    }

    /**
     * Protect this route by setting up HTTP authorization
     * @param \Charcoal\HTTP\Router\Authorization\AbstractAuthorization $auth
     * @return $this
     */
    public function useAuthorization(AbstractAuthorization $auth): self
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * Try Request object with this route, return fully-qualified controller class name or NULL
     * @param \Charcoal\HTTP\Router\Controllers\Request $request
     * @param bool $bypassHttpAuth
     * @param bool $checkClassExists
     * @return string|null
     */
    public function try(Request $request, bool $bypassHttpAuth = false, bool $checkClassExists = true): ?string
    {
        $path = $request->url->path;

        // RegEx match URL pattern
        if (!is_string($path) || !preg_match($this->matchRegExp, $path)) {
            return null;
        }

        // Route Authentication
        if ($this->auth && !$bypassHttpAuth) {
            $this->auth->authorize($request->headers);
        }

        // Find HTTP Controller
        $controllerClass = $this->controller;
        if ($this->isNamespace) {
            $pathIndex = -1;
            $controllerClass = array_map(function ($part) use (&$pathIndex) {
                $pathIndex++;
                if ($part && !in_array($pathIndex, $this->ignorePathIndexes)) {
                    return CaseStyles::PascalCase($part);
                }

                return null;
            }, explode("/", trim($path, "/")));

            $namespace = substr($this->controller, 0, -2);
            $controllerClass = sprintf('%s\%s', $namespace, implode('\\', $controllerClass));
            $controllerClass = preg_replace('/\\\{2,}/', '\\', $controllerClass);
            $controllerClass = rtrim($controllerClass, '\\');
        }

        if (!$checkClassExists) {
            return $controllerClass;
        }

        return $controllerClass && class_exists($controllerClass) ? $controllerClass : $this->fallbackController;
    }
}
