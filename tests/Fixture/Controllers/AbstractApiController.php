<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Tests\Server\Fixture\Controllers;

use Charcoal\Http\Server\Attributes\AllowedParam;
use Charcoal\Http\Server\Attributes\AllowFileUpload;
use Charcoal\Http\Server\Attributes\DisableRequestBody;
use Charcoal\Http\Server\Attributes\EnableRequestBody;
use Charcoal\Http\Server\Attributes\RejectUnrecognizedParams;
use Charcoal\Http\Server\Attributes\RequestConstraintOverride;
use Charcoal\Http\Server\Enums\RequestConstraint;

#[AllowedParam(["format", "version"])]
#[RequestConstraintOverride(RequestConstraint::maxBodyBytes, 4567)]
#[DisableRequestBody]
abstract class AbstractApiController extends AbstractBaseController
{
    #[RejectUnrecognizedParams(false)]
    public function post(): void
    {
    }

    #[EnableRequestBody]
    #[AllowFileUpload(1024)]
    public function put(): void
    {
    }
}
