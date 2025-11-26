<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Cache;

/**
 * Represents a cached response pointer with metadata and validation.
 */
final readonly class CachedResponsePointer
{
    /**
     * @param string $uniqueId
     * @param string[] $namespaces
     * @param int $validity
     * @param string|null $integrityTag
     */
    public function __construct(
        public string             $uniqueId,
        public array              $namespaces,
        public int                $validity,
        public ?string            $integrityTag = null,
    )
    {
        // Validate unique identifier
        if (!$this->uniqueId || !preg_match('/\A[a-zA-Z0-9\-:_.+@]{3,128}\z/', $this->uniqueId)) {
            throw new \InvalidArgumentException("Invalid unique identifier for cached response");
        }

        // Validate namespaces
        if ($this->namespaces) {
            $index = -1;
            foreach ($this->namespaces as $namespace) {
                $index++;
                if (!is_string($namespace) || !preg_match('/\A[a-zA-Z0-9\-_]{2,40}\z/', $namespace)) {
                    throw new \InvalidArgumentException("Invalid namespace for cached response POINTER at index: "
                        . $index);
                }
            }
        }

        // Validity
        if ($this->validity < 0 || $this->validity > 31536000) {
            throw new \InvalidArgumentException("Invalid validity for cached response:" . $this->validity);
        }

        // Integrity Tag
        if ($this->integrityTag && !preg_match('/\A[a-zA-Z0-9\-_]{2,40}\z/', $this->integrityTag)) {
            throw new \InvalidArgumentException("Invalid integrity tag for cached response");
        }
    }
}