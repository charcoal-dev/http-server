<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Internal;

use Charcoal\Http\Server\Request\RequestGateway;
use Charcoal\Http\Server\Request\Result\AbstractResult;

/**
 * This class encapsulates the request gateway and the corresponding result of the operation.
 * It provides a readonly structure to maintain immutability and ensure that the associated
 * data remains consistent once initialized.
 * @internal
 */
final readonly class RequestGatewayResult
{
    public function __construct(
        public ?RequestGateway $gateway,
        public AbstractResult  $result
    )
    {
    }
}