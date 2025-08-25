<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts\Request;

use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Router\Request\RequestContext;

/**
 * Defines the contract for a controller context, which provides access to
 * request and response handling, path parameters, and cache control directives.
 */
interface ControllerContextInterface
{
    public function __construct(RequestContext $context);

    public function request(): UnsafePayload;

    public function response(): WritablePayload;

    public function pathParams(): ?array;

    public function enforceRequiredParams(): void;

    public function setCacheControl(CacheControlDirectives $cacheControl): void;
}