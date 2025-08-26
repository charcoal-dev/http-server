<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Middleware;

use Charcoal\Base\Support\Callbacks\StaticCallback;
use Charcoal\Http\Server\Contracts\Middleware\PipelineMiddlewareInterface;
use Charcoal\Http\Server\Enums\Pipeline;

/**
 * The MiddlewareRegistry class provides a mechanism to register and manage middleware
 * associated with specific pipelines. Middleware may be persisted or runtime-based
 * and can be executed with optional fallback mechanisms.
 */
final class MiddlewareRegistry
{
    /** @var array<string, PipelineMiddlewareInterface|callable> */
    private array $persisted = [];
    /** @var array<string, PipelineMiddlewareInterface|callable> */
    private array $runtime = [];
    /** @var array<string, true> */
    private array $executed = [];

    private bool $locked = false;

    /**
     * @param Pipeline $contract
     * @param PipelineMiddlewareInterface|callable $middleware
     * @return void
     */
    public function register(Pipeline $contract, PipelineMiddlewareInterface|callable $middleware): void
    {
        if (isset($this->executed[$contract->value])) {
            throw new \RuntimeException("Middleware already dispatched for pipeline: " . $contract->name);
        }

        if ($middleware instanceof StaticCallback) {
            if ($this->locked) {
                throw new \RuntimeException("Cannot register middleware after registry is locked");
            }

            $this->persisted[$contract->value] = $middleware;
        }

        if ($middleware instanceof PipelineMiddlewareInterface || is_callable($middleware)) {
            $this->runtime[$contract->value] = $middleware;
        }
    }

    /**
     * @return void
     */
    public function lock(): void
    {
        $this->locked = true;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [
            "persisted" => $this->persisted,
            "hot" => null,
            "dispatched" => null,
            "locked" => $this->locked
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->persisted = $data["factory"];
        $this->locked = $data["locked"];
        $this->runtime = [];
        $this->executed = [];
    }

    /**
     * @param Pipeline $contract
     * @param string|null $fallback
     * @param array $params
     * @return mixed
     */
    public function execute(Pipeline $contract, ?string $fallback = null, array $params = []): mixed
    {
        $executable = match (true) {
            isset($this->runtime[$contract->value]) => $this->runtime[$contract->value],
            isset($this->persisted[$contract->value]) => $this->persisted[$contract->value],
            default => $fallback ? (new $fallback()) : null,
        };

        if (!$executable) {
            throw new \RuntimeException("Middleware not registered for pipeline: " . $contract->name);
        }

        $this->executed[$contract->value] = true;
        return is_callable($executable) ?
            $executable(...$params) : $executable->invoke(...$params);
    }
}