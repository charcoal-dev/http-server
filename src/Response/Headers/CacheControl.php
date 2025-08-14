<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Response\Headers;

use Charcoal\Http\Router\Enums\CacheStoreDirective;

/**
 * Class CacheControl
 * @package Charcoal\Http\Router\Response\Headers
 */
readonly class CacheControl
{
    public ?int $sMaxAge;

    public function __construct(
        public CacheStoreDirective $store,
        public ?int                $maxAge = null,
        ?int                       $sMaxAge = null,
        public bool                $mustRevalidate = false,
        public bool                $noCache = false,
        public bool                $immutable = false,
        public bool                $noTransform = false,
        public array               $customDirectives = []
    )
    {
        $this->sMaxAge = $sMaxAge ?? $this->maxAge;
    }

    /**
     * @return string
     */
    public function getHeaderValue(): string
    {
        $cacheControl[] = $this->store->value;
        if (is_int($this->maxAge) && $this->maxAge >= 0) {
            $cacheControl[] = "max-age=" . $this->maxAge;
        }

        if (is_int($this->sMaxAge) && $this->sMaxAge >= 0) {
            $cacheControl[] = "s-maxage=" . $this->sMaxAge;
        }

        if ($this->mustRevalidate) {
            $cacheControl[] = "must-revalidate";
        }

        if ($this->noCache) {
            $cacheControl[] = "no-cache";
        }

        if ($this->immutable) {
            $cacheControl[] = "immutable";
        }

        if ($this->noTransform) {
            $cacheControl[] = "no-transform";
        }

        // Append any additional custom directives
        foreach ($this->customDirectives as $directive) {
            if (is_string($directive)) {
                $cacheControl[] = $directive;
            }
        }

        return implode(", ", $cacheControl);
    }
}