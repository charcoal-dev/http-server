<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server\Fixture\Controllers;

use Charcoal\Http\Server\Attributes\AllowedParam;
use Charcoal\Http\Server\Attributes\EnableRequestBody;
use Charcoal\Http\Server\Attributes\RequestConstraintOverride;
use Charcoal\Http\Server\Enums\RequestConstraint;

#[AllowedParam(["format", "version"])]
#[RequestConstraintOverride(RequestConstraint::maxBodyBytes, 4567)]
abstract class AbstractApiController extends AbstractBaseController
{
    #[EnableRequestBody]
    public function put(): void
    {
    }
}
