<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Exception;

use Charcoal\Http\Router\Contracts\PromiseResponseOnDispatch;

/**
 * Class ResponseDispatchedException
 * @package Charcoal\Http\Router\Exception
 */
class ResponseDispatchedException extends \Exception
{
    protected bool $promiseResolved = false;

    public function __construct(protected readonly ?PromiseResponseOnDispatch $promise)
    {
        parent::__construct("Response has already been dispatched");
    }

    public function hasUnresolvedPromise(): bool
    {
        return !$this->promiseResolved && $this->promise;
    }

    public function resolvePromise(): void
    {
        if ($this->promiseResolved) {
            throw new \RuntimeException("Promise has already been resolved");
        }

        $this->promiseResolved = true;
        $this->promise->resolve();
    }
}