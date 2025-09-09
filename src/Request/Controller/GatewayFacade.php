<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Controller;

use Charcoal\Contracts\Buffers\ReadableBufferInterface;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Server\Contracts\Request\ControllerApiInterface;
use Charcoal\Http\Server\Enums\ControllerAttribute;
use Charcoal\Http\Server\Enums\ControllerError;
use Charcoal\Http\Server\Exceptions\Controllers\BypassEncodingException;
use Charcoal\Http\Server\Exceptions\RequestGatewayException;
use Charcoal\Http\Server\Request\RequestGateway;
use Charcoal\Http\Server\Routing\Snapshot\ControllerAttributes;

/**
 * Represents a controller API that interacts with request and response contexts.
 */
readonly class GatewayFacade implements ControllerApiInterface
{
    protected bool $enforcedRequiredParams;

    public function __construct(private RequestGateway $gateway)
    {
    }

    /**
     * Return RequestFacade object (requestId, headers, queryParams, pathParams, payload)
     */
    public function request(): RequestFacade
    {
        return $this->gateway->requestFacade;
    }

    /**
     * Returns the response object.
     */
    public function response(): WritablePayload
    {
        return $this->gateway->output;
    }

    /**
     * @return Headers
     */
    public function headers(): Headers
    {
        return $this->gateway->responseHeaders;
    }

    /**
     * @return ControllerAttributes
     */
    public function attributes(): ControllerAttributes
    {
        return $this->gateway->routeController->controller;
    }

    /**
     * @param ControllerAttribute|string $attr
     * @param bool $aggregated
     * @return mixed
     */
    public function getAttribute(ControllerAttribute|string $attr, bool $aggregated = false): mixed
    {
        return $aggregated ? $this->attributes()->getAggregatedAttributeFor($attr, $this->gateway->controllerEp)
            : $this->attributes()->getAttributeFor($attr, $this->gateway->controllerEp);
    }

    /**
     * Checks for unrecognized parameters if the configuration dictates, and throws an exception if any are found.
     * @throws RequestGatewayException
     */
    public function enforceRequiredParams(): void
    {
        if (isset($this->enforcedRequiredParams)) {
            return;
        }

        $this->enforcedRequiredParams = true;
        if ($this->getAttribute(ControllerAttribute::rejectUnrecognizedParams) === true) {
            $unrecognized = $this->request()->payload
                ->getUnrecognizedKeys(...$this->getAttribute(
                    ControllerAttribute::allowedParams,
                    aggregated: true
                ) ?? []);
            if (!empty($unrecognized)) {
                throw new RequestGatewayException(ControllerError::UnrecognizedParam, null);
            }
        }
    }

    /**
     * @throws BypassEncodingException
     * @api
     */
    public function sendResponseBypassEncoding(ReadableBufferInterface $buffer, int $statusCode = 200): never
    {
        throw new BypassEncodingException($buffer, $statusCode);
    }
}