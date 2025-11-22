<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Request\Logger;

use Charcoal\Http\Server\Contracts\Logger\LogStorageProviderInterface;

/**
 * Represents a class responsible for constructing a request logger.
 * The constructor initializes the logger with a storage provider and an optional
 * context for log entities.
 */
final readonly class RequestLoggerConstructor
{
    public function __construct(
        public LogStorageProviderInterface $logStore,
        public array                       $logEntityContext = []
    )
    {
    }
}