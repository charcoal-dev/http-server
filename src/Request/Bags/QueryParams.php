<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Bags;

use Charcoal\Http\Commons\Support\HttpHelper;

/**
 * Represents a collection of query parameters that can be extended or manipulated.
 * This class extends the functionality of AbstractDataset, enabling advanced dataset operations.
 */
final readonly class QueryParams implements \IteratorAggregate, \Countable
{
    /** @var array<string,string[]> */
    private array $bag;
    private int $count;

    public function __construct(string $queryStr)
    {
        $decoded = HttpHelper::parseQueryString(
            $queryStr,
            plusAsSpace: true,
            utf8Encoding: true,
            flatten: false
        );

        if (!$decoded) {
            $this->bag = [];
            return;
        }

        $params = [];
        foreach ($decoded as $key => $value) {
            $params[strtolower($key)] = $value;
        }

        $this->bag = $params;
        $this->count = count($this->bag);
    }

    /**
     * Retrieves the value associated with the specified key from the bag.
     * @return string[]|null
     */
    public function get(string $key): array|null
    {
        return $this->bag[strtolower($key)] ?? null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->bag[strtolower($key)]);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return array|\string[][]
     */
    public function getArray(): array
    {
        return $this->bag;
    }

    /**
     * @return \Traversable<string,string[]>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->bag);
    }
}