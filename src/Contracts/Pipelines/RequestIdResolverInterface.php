<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Contracts\Pipelines;

use Charcoal\Http\Commons\Headers\HeadersImmutable;

/**
 * Defines an interface for resolving a request ID from a set of immutable headers.
 */
interface RequestIdResolverInterface extends PipelineInterface
{
    public function __invoke(HeadersImmutable $headers): string;
}