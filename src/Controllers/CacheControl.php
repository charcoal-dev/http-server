<?php
/*
 * This file is a part of "charcoal-dev/http-router" package.
 * https://github.com/charcoal-dev/http-router
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/http-router/blob/main/LICENSE
 */
declare(strict_types=1);

namespace Charcoal\Http\Router\Controllers;

/**
 * Class CacheControl
 * @package Charcoal\Http\Router\Controllers
 */
class CacheControl
{
    /**
     * @param CacheStoreDirective $store
     * @param int|null $maxAge
     * @param int|null $sMaxAge
     * @param bool $mustRevalidate
     * @param bool $noCache
     * @param bool $immutable
     * @param bool $noTransform
     * @param array $customDirectives
     */
    public function __construct(
        public CacheStoreDirective $store,
        public ?int                $maxAge = null,
        public ?int                $sMaxAge = null,
        public bool                $mustRevalidate = false,
        public bool                $noCache = false,
        public bool                $immutable = false,
        public bool                $noTransform = false,
        public array               $customDirectives = []
    )
    {
        $this->sMaxAge = $this->sMaxAge ?? $this->maxAge;
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