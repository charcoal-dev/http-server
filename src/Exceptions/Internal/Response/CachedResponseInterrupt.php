<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Internal\Response;

use Charcoal\Http\Server\Request\Result\CachedResult;

/**
 * Represents an exception triggered to signal a cached response has been retrieved.
 * @internal
 */
final class CachedResponseInterrupt extends \Exception
{
    /**
     * @internal
     */
    public function __construct(
        public readonly CachedResult $result
    )
    {
        parent::__construct("Cached response interrupt");
    }
}