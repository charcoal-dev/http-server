<?php
/**
 * Part of the "charcoal-dev/http-router" package.
 * @link https://github.com/charcoal-dev/http-router
 */

declare(strict_types=1);

namespace Charcoal\Http\Router\Request\Result;

use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Router\Internal\Constants;

/**
 * Represents the result of a redirection, containing the redirect URL and associated headers.
 * This class is immutable and provides the necessary information for HTTP redirection,
 * including the redirect URL and any relevant headers. It extends the AbstractResult
 * class to standardize the structure of result handling.
 */
final readonly class RedirectResult extends AbstractResult
{
    public function __construct(
        Headers            $headers,
        public RedirectUrl $redirectUrl,
    )
    {
        $headers->set("Location", $this->redirectUrl->getUrl());
        $headers->set("Content-Length", "0");
        $headers->set("Cache-Control", in_array($this->redirectUrl->statusCode, [301, 308]) ?
            "public, max-age=" . Constants::PERMANENT_REDIRECT_CACHE : "no-cache, no-store, must-revalidate");
        parent::__construct($redirectUrl->statusCode, $headers);
    }
}