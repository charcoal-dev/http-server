<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Contracts;

use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Request\Request;

/**
 * Interface AuthorizationInterface
 * @package Charcoal\Http\Router\Contracts
 */
interface AuthenticatorInterface
{
    public function authenticate(Request $request): bool;

    public function onSuccess(): AuthContextInterface;

    public function onFailure(WritableHeaders $headers): void;
}