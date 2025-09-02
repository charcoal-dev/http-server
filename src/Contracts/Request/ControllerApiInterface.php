<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Request;

use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Request\Controller\RequestFacade;
use Charcoal\Http\Server\Request\RequestGateway;
use Charcoal\Http\Server\Routing\Snapshot\ControllerAttributes;

/**
 * Interface representing a controller API that manages the lifecycle of HTTP requests and responses.
 */
interface ControllerApiInterface
{
    public function __construct(RequestGateway $gateway);

    public function attributes(): ControllerAttributes;

    public function request(): RequestFacade;

    public function response(): WritablePayload;

    public function headers(): Headers;

    public function enforceRequiredParams(): void;

    public function setCacheControl(CacheControlDirectives $cacheControl): void;
}