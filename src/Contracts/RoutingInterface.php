<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts;

use Charcoal\Http\Router\Controller\AbstractController;
use Charcoal\Http\Router\Request\Request;

/**
 * Interface RoutingInterface
 * @package Charcoal\Http\Router\Contracts
 */
interface RoutingInterface
{
    /**
     * @param class-string<AbstractController> $classname
     * @return $this
     */
    public function fallbackController(string $classname): static;

    /**
     * @param Request $request
     * @return class-string<AbstractController>|AbstractController|null
     */
    public function try(Request $request): null|string|AbstractController;
}