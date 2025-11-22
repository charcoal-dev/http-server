<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Logger;

use Charcoal\Http\Server\Request\Logger\RequestLogPolicy;
use Charcoal\Http\Server\Request\RequestGateway;

/**
 * Interface LogStorageProviderInterface
 * Encapsulates methods for managing and processing request logging entities.
 */
interface LogStorageProviderInterface
{
    /**
     * @param RequestGateway $request
     * @param \Closure|null $beforeInsert
     * @param array $context
     * @return RequestLogEntityInterface
     */
    public function initLogEntity(
        RequestGateway $request,
        ?\Closure      $beforeInsert = null,
        array          $context = []
    ): RequestLogEntityInterface;

    /**
     * @param RequestLogPolicy $policy
     * @param RequestLogEntityInterface $logEntity
     * @return void
     */
    public function finishLogEntity(
        RequestLogPolicy          $policy,
        RequestLogEntityInterface $logEntity
    ): void;
}