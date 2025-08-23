<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Bag;

use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Http\Router\Middleware\MiddlewareConstructor;

/**
 * Represents a sealed container for two middleware bags: an "own" bag and an "inherited" bag.
 * Ensures that both bags are in a locked state upon instantiation.
 */
final readonly class SealedBag implements \IteratorAggregate, \Countable
{
    use NoDumpTrait;

    /** @var array<MiddlewareConstructor> */
    public array $own;
    /** @var array<MiddlewareConstructor> */
    public array $inherited;
    /** @var array<MiddlewareConstructor> */
    public array $combined;
    /** @var int */
    public int $count;

    public function __construct(
        Bag $own,
        Bag $inherited,
    )
    {
        if (!$own->isLocked()) {
            throw new \RuntimeException("Own middleware bag is not locked");
        } elseif (!$inherited->isLocked()) {
            throw new \RuntimeException("Inherited middleware bag is not locked");
        }

        $this->own = $own->getArray();
        $this->inherited = $inherited->getArray();
        $this->combined = array_merge($this->inherited, $this->own);
        $this->count = count($this->combined);
    }

    /**
     * @return array
     */
    public function getArray(): array
    {
        return array_merge($this->own, $this->inherited);
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->combined);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->combined);
    }
}