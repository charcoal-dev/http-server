<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server\Fixture\Controllers;

use Charcoal\Http\Commons\Enums\CacheControl;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Attributes\AllowedParam;
use Charcoal\Http\Server\Attributes\AllowTextBody;
use Charcoal\Http\Server\Attributes\CacheControlAttribute;
use Charcoal\Http\Server\Attributes\DisableRequestBody;
use Charcoal\Http\Server\Attributes\EnableRequestBody;
use Charcoal\Http\Server\Attributes\RejectUnrecognizedParams;
use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;

#[DisableRequestBody]
final class MethodAttributeController implements ControllerInterface
{
    #[CacheControlAttribute(new CacheControlDirectives(CacheControl::Public, maxAge: 3600))]
    #[RejectUnrecognizedParams(true)]
    #[AllowTextBody]
    public function get(): void
    {
    }

    #[AllowedParam(["filter"])]
    #[EnableRequestBody]
    #[CacheControlAttribute(new CacheControlDirectives(CacheControl::NoStore))]
    #[RejectUnrecognizedParams(false)]
    public function post(): void
    {
    }
}

