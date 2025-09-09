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
    /** @internal Sets output buffering to STDOUT; As part of response body; defaults to false */
    public static bool $outputBufferToStdOut = false;
    /** @internal Enables use of error_log() for STDERR; defaults to true */
    public static bool $useErrorLogForStdErr = true;

    /**
     * @param array|string $messages
     * @param string $header
     * @return void
     */
    public static function writeToStdErr(
        array|string $messages,
        string       $header = "Charcoal HTTP Server:"
    ): void
    {
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