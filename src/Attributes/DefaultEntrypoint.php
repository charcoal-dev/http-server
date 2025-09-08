<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;

/**
 * An attribute that specifies the default entry point method for a class.
 * This attribute is intended to be used to designate a specific method
 * as the entry point when the associated class is processed.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class DefaultEntrypoint implements ControllerAttributeInterface
{
    public string $method;

    public function __construct(string $method)
    {
        $method = trim($method);
        if ($method === "" || !preg_match("/^[A-Za-z_][A-Za-z0-9_]*$/", $method)) {
            throw new \InvalidArgumentException('DefaultEntrypoint: invalid method name');
        }

        $this->method = $method;
    }

    /**
     * @return \Closure
     */
    public function getBuilderFn(): \Closure
    {
        return fn(mixed $current, DefaultEntrypoint $attrInstance): string => $attrInstance->method;
    }
}