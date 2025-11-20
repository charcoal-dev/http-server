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
use Charcoal\Http\Server\Attributes\CacheControlAttribute;
use Charcoal\Http\Server\Attributes\EnableRequestBody;
use Charcoal\Http\Server\Attributes\RequestConstraintOverride;
use Charcoal\Http\Server\Enums\RequestConstraint;

#[CacheControlAttribute(new CacheControlDirectives(CacheControl::Private, maxAge: 0, noCache: true))]
#[RequestConstraintOverride(RequestConstraint::dtoMaxDepth, 12)]
#[AllowedParam(["concrete", "deep"])]
final class ConcreteInheritanceController extends AbstractApiController
{
    public function get(): void
    {
    }

    #[CacheControlAttribute(new CacheControlDirectives(CacheControl::Private, maxAge: 1800))]
    #[EnableRequestBody]
    public function post(): void
    {
    }
}
