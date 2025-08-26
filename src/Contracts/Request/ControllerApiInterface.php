<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Request;

use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Request\RequestGateway;

/**
 * Interface representing a controller API that manages the lifecycle of HTTP requests and responses.
 */
interface ControllerApiInterface
{
    public function __construct(RequestGateway $context);

    public function request(): UnsafePayload;

    public function response(): WritablePayload;

    public function pathParams(): ?array;

    public function enforceRequiredParams(): void;

    public function setCacheControl(CacheControlDirectives $cacheControl): void;
}