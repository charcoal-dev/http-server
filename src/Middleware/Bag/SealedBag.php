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
 * @implements \IteratorAggregate<MiddlewareConstructor>
 */
final readonly class SealedBag implements \IteratorAggregate, \Countable
{
    use NoDumpTrait;

    /** @var array<MiddlewareConstructor> */
    private array $combined;
    private int $count;

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

        $mapO = $this->openBag($own);
        $mapI = $this->openBag($inherited);
        $this->combined = array_replace($mapI, $mapO);
        $this->count = count($this->combined);
    }

    /**
     * @param Bag $bag
     * @return array<non-empty-string, MiddlewareConstructor>
     */
    private function openBag(Bag $bag): array
    {
        $map = [];
        foreach ($bag->getArray() as $ctr) {
            foreach ($ctr->binds as $contract) {
                $map[$contract] = $ctr;
            }
        }

        return $map;
    }

    /**
     * @return array
     */
    public function getArray(): array
    {
        return $this->combined;
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