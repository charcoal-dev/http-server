<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Exceptions\Controllers;

use Charcoal\Http\Server\Contracts\Request\SuccessResponseInterface;

/**
 * Represents an exception thrown when an attempt is made to modify a response that has already been finalized.
 */
abstract class ResponseFinalizedException extends \Exception
{
    public function __construct(
        public readonly int $statusCode = 200
    )
    {
        parent::__construct("Response finalized", $this->statusCode);
    }

    abstract public function getResponseObject(): SuccessResponseInterface;
}