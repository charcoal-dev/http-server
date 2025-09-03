<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Files;

/**
 * Tiny VO to manage uploaded file information
 */
final readonly class FileUpload
{
    public function __construct(
        public string $path,
        public int    $size
    )
    {
    }
}