<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Controller;

use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Contracts\Request\ControllerApiInterface;
use Charcoal\Http\Server\Enums\ControllerError;
use Charcoal\Http\Server\Exceptions\RequestGatewayException;
use Charcoal\Http\Server\Request\RequestGateway;
use Charcoal\Http\Server\Routing\Snapshot\ControllerAttributes;

/**
 * Represents a controller API that interacts with request and response contexts.
 */
readonly class GatewayFacade implements ControllerApiInterface
{
    protected bool $enforcedRequiredParams;

    public function __construct(private RequestGateway $request)
    {
    }

    /**
     * Returns the request payload object.
     */
    public function request(): UnsafePayload
    {
        return $this->request->input;
    }

    /**
     * Returns the response object.
     */
    public function response(): WritablePayload
    {
        return $this->request->output;
    }

    /**
     * @return Headers
     */
    public function headers(): Headers
    {
        return $this->request->responseHeaders;
    }

    /**
     * Returns the path parameters.
     */
    public function pathParams(): ?array
    {
        return $this->request->pathParams;
    }

    /**
     * @param CacheControlDirectives $cacheControl
     * @return void
     */
    public function setCacheControl(CacheControlDirectives $cacheControl): void
    {
        $this->request->setCacheControl($cacheControl);
    }

    /**
     * @return ControllerAttributes
     */
    public function attributes(): ControllerAttributes
    {
        return $this->request->routeController->controller->attributes;
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
        $attr = $this->attributes();
        if ($attr->rejectUnrecognizedParams) {
            $unrecognized = $this->request->input->getUnrecognizedKeys(...$attr->allowedParams);
            if (!empty($unrecognized)) {
                throw new RequestGatewayException(ControllerError::UnrecognizedParam, null);
            }
        }
    }
}