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
use Charcoal\Http\Server\Attributes\DisableRequestBody;
use Charcoal\Http\Server\Attributes\RequestConstraintOverride;
use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Server\Enums\RequestConstraint;

#[CacheControlAttribute(new CacheControlDirectives(CacheControl::Public, maxAge: 600))]
#[AllowedParam(["common", "base"])]
#[RequestConstraintOverride(RequestConstraint::maxBodyBytes, 123)]
#[RequestConstraintOverride(RequestConstraint::dtoMaxDepth, 456)]
#[RequestConstraintOverride(RequestConstraint::maxParams, 789)]
#[DisableRequestBody]
abstract class AbstractBaseController implements ControllerInterface
{
}