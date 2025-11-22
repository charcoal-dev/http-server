<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Logger;

use Charcoal\Http\Server\Contracts\Logger\LogStorageProviderInterface;
use Charcoal\Http\Server\Contracts\Logger\RequestLogEntityInterface;
use Charcoal\Http\Server\Request\Controller\ResponseFacade;
use Charcoal\Http\Server\Request\RequestGateway;
use Charcoal\Http\Server\Request\Result\AbstractResult;

/**
 * Represents a logger designated for handling request logs.
 * This class is responsible for managing logging policies and identifying specific entities
 * to log. It provides a unique identifier and timestamp marking the start of a request.
 */
final class RequestLogger
{
    private ?RequestLogPolicy $policy = null;
    private readonly RequestLogEntityInterface $logEntity;
    private readonly LogStorageProviderInterface $logStorage;
    private readonly array $requestLogContext;

    /**
     * @param RequestLoggerConstructor $policy
     */
    public function __construct(RequestLoggerConstructor $policy)
    {
        $this->logStorage = $policy->logStore;
        $this->requestLogContext = $policy->logEntityContext;
    }

    /**
     * @param RequestLogPolicy $policy
     * @return void
     */
    public function setPolicy(RequestLogPolicy $policy): void
    {
        $this->policy = $policy;
    }

    /**
     * @return RequestLogPolicy|null
     */
    public function getPolicy(): ?RequestLogPolicy
    {
        return $this->policy;
    }

    /**
     * @return RequestLogEntityInterface|null
     */
    public function isLogging(): ?RequestLogEntityInterface
    {
        return $this->logEntity ?? null;
    }

    /**
     * @param RequestGateway $gateway
     * @param \Closure|null $beforeInsert
     * @return void
     */
    public function initializeLogging(RequestGateway $gateway, ?\Closure $beforeInsert = null): void
    {
        if (isset($this->logEntity)) {
            throw new \BadMethodCallException("RequestLogger already initialized");
        }

        if (!$this->policy || !$this->policy->enabled) {
            return;
        }

        $this->logEntity = $this->logStorage->initLogEntity($gateway, $beforeInsert, $this->requestLogContext);
    }

    /**
     * @param ResponseFacade $response
     * @return void
     */
    public function populateResponseBody(ResponseFacade $response): void
    {
        if (!isset($this->logEntity)) {
            return;
        }

        if (!$this->policy || !$this->policy->enabled || !$this->policy->responseParams) {
            return;
        }

        $this->logEntity->setResponseData($response);
    }

    /**
     * @param AbstractResult $result
     * @param float $startedOn
     * @return void
     */
    public function finalizeLogEntity(
        AbstractResult $result,
        float          $startedOn
    ): void
    {
        if (!$this->policy || !$this->policy->enabled || !isset($this->logEntity)) {
            return;
        }

        // Populate Log Entity
        $this->logEntity->setResponseCode($result->statusCode);
        $this->logEntity->finalizeLogEntity($startedOn);
        if ($this->policy->responseHeaders) {
            $this->logEntity->setResponseHeaders($result->headers);
        }

        $this->logStorage->finishLogEntity(
            $this->policy,
            $this->logEntity
        );
    }
}