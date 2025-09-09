<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Attributes;

use Charcoal\Http\Server\Contracts\Controllers\ControllerAttributeInterface;

/**
 * Represents an attribute that allows a file upload operation,
 * with an optional restriction on the maximum file size.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class AllowFileUpload implements ControllerAttributeInterface
{
    public int $maxFileSize;

    public function __construct(int $maxFileSize = 0)
    {
        if ($maxFileSize < 0) {
            throw new \InvalidArgumentException("Max file size must be a positive integer");
        }

        $this->maxFileSize = $maxFileSize;
    }

    /**
     * @return \Closure
     */
    public function getBuilderFn(): \Closure
    {
        return fn(
            mixed           $current,
            AllowFileUpload $attrInstance
        ): array => ["size" => $attrInstance->maxFileSize];
    }
}