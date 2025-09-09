<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Internal;

/**
 * Interface containing a set of constants for HTTP methods and path validation.
 * @internal
 */
interface Constants
{
    public const string CHARCOAL_HTTP_SERVER = "Charcoal HTTP Server/0.2.0";

    /**
     * Hard limits for request body and file uploads
     */
    public const int HARD_LIMIT_MEMORY_REQ_BODY = 1048576;
    public const int HARD_LIMIT_REQ_UPLOAD = 10485760;

    /**
     * Wildcard placeholder for "default" or "any" HTTP methods
     * @var string
     */
    public const string METHOD_ANY = "(*)";

    /**
     * Path validations RegExp literals
     * @var non-empty-string
     */
    // language=RegExp
    public const string PATH_VALIDATION_REGEXP = "~^(?:[A-Za-z0-9_.-]*[A-Za-z0-9]|" . self::PARAM_FORMAT_REGEXP . ")" .
    "(?:/(?:[A-Za-z0-9_.-]*[A-Za-z0-9]|" . self::PARAM_FORMAT_REGEXP . "))*$~";
    public const string PARAM_FORMAT_REGEXP = ":[A-Za-z0-9]+";

    // language=RegExp
    public const string PARAM_NAME_CAPTURE_REGEXP = "/\\\\:([A-Za-z0-9_]+)/";
    // language=RegExp
    public const string PARAM_NAME_PLACEHOLDER = "([^/]+)";

    /**
     * Cache duration for permanent redirects
     * @var int
     */
    public const int PERMANENT_REDIRECT_CACHE = 604800;
}