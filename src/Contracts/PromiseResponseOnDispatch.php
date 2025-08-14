<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts;

use Charcoal\Http\Commons\Header\WritableHeaders;

/**
 * Interface PromiseResponseOnDispatch
 * @package Charcoal\Http\Router\Contracts\Response
 */
interface PromiseResponseOnDispatch
{
    public function setHeaders(WritableHeaders $headers): void;

    public function resolve(): void;
}