<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router;

use Charcoal\Base\Support\Helpers\CaseStyleHelper;
use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Contracts\Auth\AuthenticatorInterface;
use Charcoal\Http\Router\Contracts\RoutingInterface;
use Charcoal\Http\Router\Controller\AbstractController;
use Charcoal\Http\Router\Request\Request;

/**
 * Class Route
 * @package Charcoal\Http\Router\Route
 */
class Route implements RoutingInterface
{
    public readonly int $num;
    public readonly string $path;
    public readonly string $matchRegExp;
    public readonly string $controller;
    public readonly bool $isNamespace;
    private array $ignorePathIndexes = [];
    private ?string $fallbackController = null;

    private ?AuthenticatorInterface $auth = null;

    use NoDumpTrait;
    use NotCloneableTrait;
    use NotSerializableTrait;

    public function __construct(
        public readonly Router $router,
        string                 $path,
        string                 $namespaceOrClass
    )
    {
        $this->num = $this->router->routerCount() + 1;

        // URL Path
        $path = "/" . trim(strtolower($path), "/"); // Case-insensitivity
        if (!preg_match('/^((\/?[\w\-.]+)|(\/\*))*(\/\*)?$/', $path)) {
            throw new \InvalidArgumentException(
                sprintf("Route #%d URL path argument contain an illegal character", $this->num));
        }

        // Controller or Namespace
        if (!preg_match('/^\w+(\\\\\w+)*(\\\\\*)?$/i', $namespaceOrClass)) {
            throw new \InvalidArgumentException(
                sprintf("Class or namespace for route #%d contains an illegal character", $this->num));
        }

        $urlIsWildcard = str_ends_with($path, '/*');
        $controllerIsWildcard = str_ends_with($namespaceOrClass, '\*');
        if ($controllerIsWildcard && !$urlIsWildcard) {
            throw new \InvalidArgumentException(sprintf('Route #%d URL must end with "/*"', $this->num));
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
    public function fallbackController(string $controller): static
    {
        if (!class_exists($controller)) {
            throw new \InvalidArgumentException(
                sprintf("Fallback controller for route #%d class is invalid or does not exist", $this->num));
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

        // Finalize and return
        return $pattern . "$/";
    }

    /**
     * The following path indexes will be ignored while routing to a classname
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
     * @param \Charcoal\Http\Router\Authorization\AbstractAuthorization $auth
     * @return $this
     */
    public function useAuthorization(AuthenticatorInterface $auth): self
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * @return AuthenticatorInterface|null
     */
    public function isProtected(): ?AuthenticatorInterface
    {
        return $this->auth;
    }

    /**
     * @param Request $request
     * @return class-string<AbstractController>|null
     */
    public function try(Request $request): ?string
    {
        $controllerClass = $this->getControllerClass($request->url->path);
        return $controllerClass && class_exists($controllerClass) ?
            $controllerClass : $this->fallbackController;
    }

    /**
     * @param string $path
     * @return class-string<AbstractController>|null
     */
    public function getControllerClass(string $path): ?string
    {
        if (!$path || !preg_match($this->matchRegExp, $path)) {
            return null;
        }

        $controllerClass = $this->controller;
        if ($this->isNamespace) {
            $pathIndex = -1;
            $controllerClass = array_map(function ($part) use (&$pathIndex) {
                $pathIndex++;
                if ($part && !in_array($pathIndex, $this->ignorePathIndexes)) {
                    return CaseStyleHelper::pascalCaseFromRaw($part);
                }

                return null;
            }, explode("/", trim($path, "/")));

            $namespace = substr($this->controller, 0, -2);
            $controllerClass = sprintf('%s\%s', $namespace, implode('\\', $controllerClass));
            $controllerClass = preg_replace('/\\\{2,}/', '\\', $controllerClass);
            $controllerClass = rtrim($controllerClass, '\\');
        }

        return $controllerClass;
    }
}
