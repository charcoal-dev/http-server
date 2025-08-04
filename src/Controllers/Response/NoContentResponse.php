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

namespace Charcoal\Http\Router\Controllers\Response;

/**
 * Class NoContentResponse
 * @package Charcoal\Http\Router\Controllers\Response
 */
class NoContentResponse extends AbstractControllerResponse
{
    public function __construct(int $statusCode, AbstractControllerResponse $createFrom)
    {
        parent::__construct($statusCode, $createFrom->headers);
    }

    protected function beforeSendResponseHook(): void
    {
    }

    protected function sendBody(): void
    {
    }
}

