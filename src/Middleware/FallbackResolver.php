<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware;

use Charcoal\Http\Router\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Router\Contracts\Middleware\Kernel\KernelMiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;
use Charcoal\Http\Router\Contracts\Middleware\MiddlewareResolverInterface;
use Charcoal\Http\Router\Enums\Middleware\KernelPipelines;
use Charcoal\Http\Router\Enums\Middleware\Scope;
use Charcoal\Http\Router\Middleware\Kernel\RequestIdResolver;
use Charcoal\Http\Router\Middleware\Kernel\UrlEncodingEnforcer;

/**
 * This class implements a fallback middleware resolver.
 * It is used when no specific middleware resolver is found for a given contract.
 */
final readonly class FallbackResolver implements MiddlewareResolverInterface
{
    /**
     * @param string $contract
     * @param ControllerInterface $controller
     * @param Scope $scope
     * @param array|null $context
     * @return MiddlewareInterface|callable
     */
    public function resolveFor(
        string              $contract,
        ControllerInterface $controller,
        Scope               $scope = Scope::Group,
        ?array              $context = null
    ): MiddlewareInterface|callable
    {
        $kernel = KernelPipelines::tryFrom($contract);
        if ($kernel) {
            return $this->resolveForKernel($kernel);
        }

        throw new \RuntimeException("No middleware resolver found for contract: " . $contract);
    }

    /**
     * @param KernelPipelines $pipeline
     * @return KernelMiddlewareInterface
     */
    public function resolveForKernel(KernelPipelines $pipeline): MiddlewareInterface
    {
        return match ($pipeline) {
            KernelPipelines::RequestID_Resolver => new RequestIdResolver(),
            KernelPipelines::URL_EncodingEnforcer => new UrlEncodingEnforcer(),
            default => throw new \RuntimeException($pipeline->name . " is not available in FallbackResolver"),
        };
    }
}