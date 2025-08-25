<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Attributes\Controllers;

/**
 * An attribute that specifies the default entry point method for a class.
 * This attribute is intended to be used to designate a specific method
 * as the entry point when the associated class is processed.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class DefaultEntrypoint
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
}