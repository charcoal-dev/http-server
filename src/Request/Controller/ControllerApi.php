<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Controller;

use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Contracts\Request\ControllerApiInterface;
use Charcoal\Http\Server\Request\RequestLifecycle;

/**
 * Represents a controller API that interacts with request and response contexts.
 */
readonly class ControllerApi implements ControllerApiInterface
{
    public function __construct(private RequestLifecycle $request)
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