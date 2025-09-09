<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Pipelines;

use Charcoal\Base\Arrays\DtoHelper;
use Charcoal\Base\Support\Runtime;
use Charcoal\Buffers\BufferImmutable;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Http\Commons\Body\PayloadImmutable;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Server\Contracts\Middleware\ResponseBodyEncoderPipeline;
use Charcoal\Http\Server\Request\Result\Response\EncodedResponseBody;

/**
 * The ResponseBodyEncoder class is responsible for encoding response payloads
 * according to the specified content type. It provides methods to handle
 * encoding for different content types such as JSON, XML, or a fallback mechanism.
 * It is part of a response body encoding pipeline.
 */
class ResponseBodyEncoder implements ResponseBodyEncoderPipeline
{
    public static bool $hammerPayload = true;
    public static string $xmlRootTag = "result";

    /**
     * @see ResponseBodyEncoderPipeline
     */
    final public function __invoke(
        ContentType      $contentType,
        Charset          $charset,
        PayloadImmutable $response
    ): EncodedResponseBody
    {
        $response = $response->getArray();
        if (static::$hammerPayload) {
            $response = DtoHelper::createFrom($response, 32, true, true, "**RECURSION**");
            Runtime::assert(is_array($response),
                "Payload result after DTO processing is not an Array, got " . get_debug_type($response));
        }

        $encoded = $this->encodePayloadIn($contentType, $charset, $response);
        if ($encoded) {
            return $encoded;
        }

        return $this->fallbackEncoding($charset, $response);
    }

    /**
     * Extend this class, and this method, to add support for additional content types.
     */
    protected function encodePayloadIn(
        ContentType $contentType,
        Charset     $charset,
        array       $payload
    ): ?EncodedResponseBody
    {
        if ($contentType === ContentType::Json) {
            return new EncodedResponseBody(ContentType::Json, $charset, $this->handleJsonEncoding($payload));
        }

        if ($contentType === ContentType::FormSubmit) {
            return new EncodedResponseBody(ContentType::FormSubmit, $charset, $this->handleFormUrlEncoding($payload));
        }

        if ($contentType === ContentType::Xml) {
            return $this->handleXmlEncoding($payload);
        }

        return null;
    }

    /**
     * @param array $payload
     * @return BufferImmutable
     */
    final public function handleFormUrlEncoding(array $payload): BufferImmutable
    {
        return new BufferImmutable(http_build_query($payload, "", "&", PHP_QUERY_RFC3986));
    }

    /**
     * @param array $payload
     * @return BufferImmutable
     */
    final public function handleJsonEncoding(array $payload): BufferImmutable
    {
        try {
            return new BufferImmutable(json_encode($payload, flags: JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new \RuntimeException("JSON encoding error: " . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @noinspection PhpComposerExtensionStubsInspection
     */
    public function handleXmlEncoding(array $payload): ?EncodedResponseBody
    {
        if (!class_exists(\SimpleXMLElement::class)) {
            return null;
        }

        try {
            $xml = new \SimpleXMLElement("<" . self::$xmlRootTag . "/>");
            $add = static function (array $data, \SimpleXMLElement $element) use (&$add): void {
                foreach ($data as $key => $value) {
                    $key = is_numeric($key) ? "item" : $key;

                    if (is_array($value)) {
                        $child = $element->addChild($key);
                        $add($value, $child);
                    } else {
                        $element->addChild($key, htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"));
                    }
                }
            };
        } catch (\Exception $e) {
            throw new \RuntimeException("XML encoding error: " . $e->getMessage(), previous: $e);
        }

        $add($payload, $xml);
        return new EncodedResponseBody(ContentType::Xml, Charset::UTF8,
            new BufferImmutable($xml->asXML() ?: throw new \RuntimeException("Failed to serialize XML")));
    }

    /**
     * Fallback method that encodes unsupported content types as plain text.
     */
    final public function fallbackEncoding(Charset $charset, array $payload): EncodedResponseBody
    {
        return new EncodedResponseBody(
            ContentType::Text,
            $charset,
            new BufferImmutable(print_r($payload, true))
        );
    }

    /**
     * @param array $params
     * @return EncodedResponseBody
     */
    final public function execute(array $params): EncodedResponseBody
    {
        return $this->__invoke(...$params);
    }
}