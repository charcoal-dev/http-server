<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;
use Charcoal\Http\Server\Enums\RequestLogPolicyEnum;
use Charcoal\Http\Server\Request\Logger\RequestLogPolicy;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class LogRequest implements ControllerAttributeInterface
{
    public function __construct(public RequestLogPolicy|RequestLogPolicyEnum $policy)
    {
    }

    public function getBuilderFn(): \Closure
    {
        return fn(mixed $current, LogRequest $attrInstance): RequestLogPolicy|RequestLogPolicyEnum => $attrInstance->policy;
    }
}