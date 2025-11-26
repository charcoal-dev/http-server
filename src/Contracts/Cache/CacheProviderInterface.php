<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Cache;

use Charcoal\Http\Server\Request\Cache\CachedResponsePointer;
use Charcoal\Http\Server\Request\Result\CachedResult;

/**
 * Interface representing a cache provider for storing and retrieving cached results.
 */
interface CacheProviderInterface
{
    public function get(CachedResponsePointer $pointer): ?CachedResult;

    public function store(CachedResponsePointer $pointer, CachedResult $result): void;

    public function delete(CachedResponsePointer $pointer): void;

    public function getTimestamp(): \DateTimeImmutable;
}