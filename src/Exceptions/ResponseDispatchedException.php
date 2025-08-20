<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Exceptions;

use Charcoal\Http\Router\Contracts\Response\ResponsePromisedOnDispatch;

/**
 * Class ResponseDispatchedException
 * @package Charcoal\Http\Router\Exceptions
 */
class ResponseDispatchedException extends \Exception
{
    protected bool $promiseResolved = false;

    public function __construct(protected readonly ?ResponsePromisedOnDispatch $promise)
    {
        parent::__construct("Response has already been dispatched");
    }

    /**
     * @return bool
     * @api
     */
    public function hasUnresolvedPromise(): bool
    {
        return !$this->promiseResolved && $this->promise;
    }

    /**
     * @return void
     * @api
     */
    public function resolvePromise(): void
    {
        if ($this->promiseResolved) {
            throw new \RuntimeException("Promise has already been resolved");
        }

        $this->promiseResolved = true;
        $this->promise->resolve();
    }
}