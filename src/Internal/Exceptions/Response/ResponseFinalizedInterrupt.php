<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Internal\Exceptions\Response;

use Charcoal\Http\Server\Contracts\Request\SuccessResponseInterface;

/**
 * Interrupt during controller execution to finalize the response.
 */
abstract class ResponseFinalizedInterrupt extends \Exception
{
    /** @internal  */
    public function __construct(
        public readonly int $statusCode = 200
    )
    {
        parent::__construct("Response finalized", $this->statusCode);
    }

    /** @internal  */
    abstract public function getResponseObject(): SuccessResponseInterface;
}