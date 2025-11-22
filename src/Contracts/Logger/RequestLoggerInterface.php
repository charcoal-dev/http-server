<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Logger;

use Charcoal\Http\Server\Request\RequestGateway;

/**
 * Interface RequestLoggerInterface
 * Encapsulates methods for managing and processing request logging entities.
 */
interface RequestLoggerInterface
{
    /**
     * @param string $uuid
     * @param RequestGateway $request
     * @param \Closure|null $beforeInsert
     * @param array $context
     * @return RequestLogEntityInterface
     * @api
     */
    public function initLogEntity(
        string         $uuid,
        RequestGateway $request,
        ?\Closure      $beforeInsert = null,
        array          $context = []
    ): RequestLogEntityInterface;

    /**
     * @param RequestLogEntityInterface $logEntity
     * @return void
     * @api
     */
    public function finishLogEntity(
        RequestLogEntityInterface $logEntity
    ): void;
}