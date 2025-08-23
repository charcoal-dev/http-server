<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Middleware\Bag;

use Charcoal\Http\Router\Contracts\Middleware\MiddlewareInterface;

/**
 * Represents a sealed container for two middleware bags: an "own" bag and an "inherited" bag.
 * Ensures that both bags are in a locked state upon instantiation.
 */
final readonly class SealedBag
{
    /** @var array<class-string<MiddlewareInterface>> */
    public array $own;
    /** @var array<class-string<MiddlewareInterface>> */
    public array $inherited;

    public function __construct(
        public string $owner,
        Bag           $own,
        Bag           $inherited,
    )
    {
        if (!$own->isLocked()) {
            throw new \RuntimeException("Own middleware bag is not locked");
        } elseif (!$inherited->isLocked()) {
            throw new \RuntimeException("Inherited middleware bag is not locked");
        }

        $this->own = array_unique($own->all());
        $this->inherited = array_unique($inherited->all());
    }
}