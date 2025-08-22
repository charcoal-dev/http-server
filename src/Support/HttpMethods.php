<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Support;

use Charcoal\Base\Vectors\AbstractEnumVector;
use Charcoal\Http\Commons\Enums\HttpMethod;

/**
 * Represents a collection of HTTP methods as an enumeration vector.
 */
final class HttpMethods extends AbstractEnumVector
{
    public function __construct(HttpMethod ...$methods)
    {
        parent::__construct(...$methods);
    }
}