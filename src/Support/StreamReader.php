<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Support;

use Charcoal\Base\Support\Helpers\ErrorHelper;

/**
 * Provides methods to read data from a stream and store it either in memory
 * or in a temporary file on disk. Offers utilities to process and transfer
 * stream data with support for limiting the read size.
 */
class StreamReader
{
    /**
     * @param string $stream
     * @param int $limit
     * @return false|array
     */
    public static function readStreamToTemp(string $stream, int $limit): false|array
    {
        if ($limit <= 0) {
            return false;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), "charcoal-");
        if (!$tmpPath) {
            throw new \RuntimeException("Failed to create temporary file for reading: ");
        }

        error_clear_last();
        $temp = @fopen($tmpPath, "wb");
        if (!$temp) {
            $tempFileError = ErrorHelper::lastErrorToRuntimeException();
            @unlink($tmpPath);
            throw new \RuntimeException("Failed to open temp file for writing", previous: $tempFileError);
        }

        try {
            $fileSize = self::readStreamAndWrite($stream, $temp, $limit);
        } catch (\RuntimeException $e) {
            @unlink($tmpPath);
            throw $e;
        } finally {
            @fclose($temp);
        }

        return ["tmpPath" => $tmpPath, "size" => $fileSize];
    }

    /**
     * @param string $stream
     * @param int $limit
     * @return string
     */
    public static function readStreamToMemory(string $stream, int $limit): string
    {
        if ($limit <= 0) {
            return "";
        }

        error_clear_last();
        $mem = @fopen("php://temp/maxmemory:" . $limit, "w+b");
        if (!$mem) {
            throw new \RuntimeException("Failed to open temp stream",
                previous: ErrorHelper::lastErrorToRuntimeException());
        }

        try {
            self::readStreamAndWrite($stream, $mem, $limit);
            rewind($mem);
            return stream_get_contents($mem) ?: "";
        } finally {
            @fclose($mem);
        }
    }

    /**
     * @param string $stream
     * @param mixed $fp2
     * @param int $limit
     * @return int
     */
    private static function readStreamAndWrite(string $stream, mixed $fp2, int $limit): int
    {
        error_clear_last();
        $buffered = 0;
        $chunkSize = 1 << 20;
        $fp1 = @fopen($stream, "rb");
        if (!$fp1) {
            throw new \RuntimeException("Failed to open stream for reading: " . $stream,
                previous: ErrorHelper::lastErrorToRuntimeException());
        }

        try {
            while (!feof($fp1)) {
                $remaining = $limit - $buffered;
                if ($remaining <= 0) {
                    break;
                }

                $bytes = self::readFromAndWriteTo($fp1, $fp2, min($chunkSize, $remaining));
                if ($bytes <= 0) {
                    break;
                }

                if (($buffered + $bytes) > $limit) {
                    throw new \OverflowException(
                        sprintf("Stream body too large; Expected %d bytes, got %d", $limit, ($buffered + $bytes))
                    );
                }

                $buffered += $bytes;
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException("Stream read terminated; Check previous", previous: $e);
        } finally {
            @fclose($fp1);
        }

        return $buffered;
    }

    /**
     * @param mixed $read
     * @param mixed $write
     * @param int $limit
     * @return int
     */
    private static function readFromAndWriteTo(mixed $read, mixed $write, int $limit): int
    {
        error_clear_last();
        $chunk = @fread($read, $limit);
        if ($chunk === false) {
            throw new \RuntimeException("Failed to read from input stream",
                previous: ErrorHelper::lastErrorToRuntimeException());
        }

        if ($chunk === "") {
            return 0;
        }

        $chunkSize = strlen($chunk);
        if ($chunkSize > 0) {
            self::writeChunk($write, $chunk, $chunkSize);
        }

        return $chunkSize;
    }

    /**
     * Writes a chunk of data to the specified stream resource.
     */
    private static function writeChunk(mixed $fp, string $chunk, int $length): void
    {
        error_clear_last();
        $wrote = 0;
        while ($wrote < $length) {
            $written = @fwrite($fp, $chunk, $length - $wrote);
            if ($written === false) {
                throw new \RuntimeException("Failed to write to temp stream",
                    previous: ErrorHelper::lastErrorToRuntimeException());
            }

            $wrote += $written;
            if ($wrote < $length) {
                $chunk = substr($chunk, $written);
            }
        }
    }
}