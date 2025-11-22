<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Logger;

use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Contracts\PayloadInterface;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Server\Contracts\Controllers\Auth\AuthContextInterface;
use Charcoal\Http\Server\Request\Bags\QueryParams;

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
     * @param HeadersImmutable $headers
     * @return void
     * @api
     */
    public function setRequestHeaders(HeadersImmutable $headers): void;

    /**
     * @param QueryParams $queryParams
     * @param UnsafePayload|null $payload
     * @return void
     * @api
     */
    public function setRequestParams(QueryParams $queryParams, ?UnsafePayload $payload): void;

    /**
     * @param int $responseCode
     * @return void
     * @api
     */
    public function setResponseCode(int $responseCode): void;

    /**
     * @param HeadersImmutable|Headers $headers
     * @return void
     * @api
     */
    public function setResponseHeaders(HeadersImmutable|Headers $headers): void;

    /**
     * @param PayloadInterface|null $payload
     * @param string|null $cachedId
     * @return void
     * @api
     */
    public function setResponseData(?PayloadInterface $payload, ?string $cachedId = null): void;

    /**
     * @param AuthContextInterface $authContext
     * @return void
     * @api
     */
    public function setAuthenticationData(AuthContextInterface $authContext): void;

    /**
     * @param float|null $startTime
     * @return void
     * @api
     */
    public function finalizeLogEntity(?float $startTime): void;
}