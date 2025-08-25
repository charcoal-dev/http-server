<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request;

use Charcoal\Http\Commons\Support\HttpHelper;

/**
 * Defines a Cross-Origin Resource Sharing (CORS) policy.
 * This class represents the configuration for managing CORS requests, specifying
 * allowed origins, HTTP methods, maximum age for preflight requests, and whether
 * credentials are included in cross-origin requests.
 */
final readonly class CorsPolicy
{
    public array $origins;

    /**
     * Constructor for initializing CORS settings.
     */
    public function __construct(
        ?array        $origins,
        public string $methods = "GET, POST, PUT, DELETE, OPTIONS, HEAD",
        public string $allow = "Content-Type, Content-Length, Authorization",
        public string $expose = "Location, ETag, Retry-After, Content-Disposition, Content-Transfer-Encoding",
        public int    $maxAge = 3600,
        public bool   $withCredentials = false,
    )
    {
        $final = [];
        foreach ($origins as $origin) {
            if (!HttpHelper::isValidOrigin($origin)) {
                throw new \InvalidArgumentException("Invalid CORS origin: " . $origin);
            }

            $final[] = strtolower($origin);
        }

        $this->origins = $final;
        if (!$this->origins && $this->withCredentials) {
            throw new \LogicException("Credentials are not allowed without an origin");
        }

        if ($this->maxAge < 0) {
            throw new \OutOfRangeException("Max age must be a non-negative integer");
        }
    }
}