<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Router\Contracts\Request\ControllerContextInterface;

/**
 * Defines the controller context, which provides access to request and response handling,
 * path parameters, and cache control directives.
 */
readonly class ControllerContext implements ControllerContextInterface
{
    public function __construct(private RequestContext $request)
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
        return $this->request->response;
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

    public function enforceRequiredParams(): void
    {
        // Todo: pending
    }
}