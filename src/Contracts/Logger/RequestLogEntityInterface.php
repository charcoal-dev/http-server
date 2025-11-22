<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Logger;

use Charcoal\Http\Commons\Contracts\HeadersInterface;
use Charcoal\Http\Commons\Contracts\PayloadInterface;
use Charcoal\Http\Server\Request\Controller\RequestFacade;

/**
 * Interface for defining the structure and behavior of a Request Log Entity.
 * Provides methods for setting metadata, request information, response details,
 * authentication data, and finalizing the log entity state.
 */
interface RequestLogEntityInterface
{
    /**
     * @param string $controllerFqcn
     * @param string $entrypoint
     * @return void
     * @api
     */
    public function setControllerMetadata(string $controllerFqcn, string $entrypoint): void;

    /**
     * @param RequestFacade $request
     * @return void
     * @api
     */
    public function setRequestData(RequestFacade $request): void;

    /**
     * @param int $responseCode
     * @return void
     * @api
     */
    public function setResponseCode(int $responseCode): void;

    /**
     * @param HeadersInterface $headers
     * @return void
     * @api
     */
    public function setResponseHeaders(HeadersInterface $headers): void;

    /**
     * @param PayloadInterface|null $payload
     * @param string|null $cachedId
     * @return void
     * @api
     */
    public function setResponseData(?PayloadInterface $payload, ?string $cachedId = null): void;

    /**
     * @return void
     * @api
     */
    public function setAuthenticationData(): void;

    /**
     * @param float|null $startTime
     * @return void
     * @api
     */
    public function finalizeLogEntity(?float $startTime): void;
}