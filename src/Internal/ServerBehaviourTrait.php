<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Internal;

/**
 * This trait contains static properties that allow customization of server settings,
 * such as whether to expose server-specific information and to enable output buffering.
 * @internal
 */
trait ServerBehaviourTrait
{
    /** @var bool Sends "Charcoal/{version}" as "X-Powered-By" header; defaults to true */
    public static bool $exposeCharcoalServer = true;

    /** @internal Enables output buffering; defaults to false */
    public static bool $enableOutputBuffering = false;
    /** @internal Sinks output buffering to STDERR; defaults to false */
    public static bool $outputBufferToStdErr = false;
    /** @internal Enables use of error_log() for STDERR; defaults to true */
    public static bool $useErrorLogForStdErr = true;

    /**
     * @internal
     */
    public static function flushOutputBuffer(): false|array
    {
        if (!self::$enableOutputBuffering) {
            return false;
        }

        $buffered = ob_get_level() > 0 ? ob_get_clean() : false;
        if (!$buffered) {
            return false;
        }

        $buffered = preg_split("/\r\n|\r|\n/", $buffered);
        if (self::$outputBufferToStdErr) {
            self::writeToStdErr($buffered);
        }

        return $buffered;
    }

    /**
     * @internal
     */
    private static function writeToStdErr(
        array|string $messages,
        string       $header = "Charcoal HTTP Server:"
    ): void
    {
        if (!$messages) {
            return;
        }

        $messages = is_array($messages) ? $messages : [$messages];
        $messages = array_filter($messages, fn($ln) => is_string($ln) && $ln);

        $message = PHP_EOL . $header . PHP_EOL;
        $message .= str_repeat("-", strlen($header)) . PHP_EOL;
        $message .= implode(PHP_EOL, $messages) . PHP_EOL;
        $message .= str_repeat("-", strlen($header)) . PHP_EOL;

        if (self::$useErrorLogForStdErr) {
            error_log($message);
        } else {
            fwrite(STDERR, $message);
        }
    }
}