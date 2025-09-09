<?php
/**
 * Part of the "charcoal-dev/http-server" package.
 * @link https://github.com/charcoal-dev/http-server
 */

declare(strict_types=1);

namespace Charcoal\Http\Server\Middleware;

use Charcoal\Base\Dataset\BatchEnvelope;
use Charcoal\Buffers\Buffer;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Http\Commons\Body\PayloadImmutable;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\HeaderKeyValidation;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Commons\Url\UrlInfo;
use Charcoal\Http\Server\Config\VirtualHost;
use Charcoal\Http\Server\Enums\Pipeline;
use Charcoal\Http\Server\Pipelines\ControllerGatewayFacadeResolver;
use Charcoal\Http\Server\Pipelines\RequestBodyDecoder;
use Charcoal\Http\Server\Pipelines\RequestHeadersValidator;
use Charcoal\Http\Server\Pipelines\ResponseBodyEncoder;
use Charcoal\Http\Server\Pipelines\UrlValidator;
use Charcoal\Http\Server\Request\Controller\GatewayFacade;
use Charcoal\Http\Server\Request\Controller\RequestFacade;
use Charcoal\Http\Server\Request\RequestGateway;
use Charcoal\Http\Server\Request\Result\Redirect\RedirectUrl;
use Charcoal\Http\Server\Request\Result\Response\EncodedResponseBody;
use Charcoal\Http\TrustProxy\Result\TrustGatewayResult;

/**
 * Represents a middleware facade responsible for managing and executing middleware pipelines.
 * Provides a structured mechanism to handle middleware execution for various workflows.
 */
final readonly class MiddlewareFacade
{
    public function __construct(private MiddlewareRegistry $registry)
    {
    }

    /**
     * Executes the URL validation pipeline with the specified URL and constraints.
     */
    public function urlValidationPipeline(UrlInfo $url, int $maxUriBytes): ?RedirectUrl
    {
        return $this->registry->execute(Pipeline::URL_Validator,
            UrlValidator::class,
            [$url, $maxUriBytes]
        );
    }

    /**
     * Executes the header validation pipeline with the specified headers,
     * constraints, and key validation rules.
     */
    public function headerValidationPipeline(
        HeadersImmutable    $headers,
        int                 $maxHeaders,
        int                 $maxHeaderLength,
        HeaderKeyValidation $keyValidation
    ): HeadersImmutable
    {
        return $this->registry->execute(Pipeline::Request_HeadersValidator,
            RequestHeadersValidator::class,
            [$headers, $maxHeaders, $maxHeaderLength, $keyValidation]
        );
    }

    /**
     * Executes the controller request facade pipeline with the specified request gateway.
     */
    public function controllerGatewayFacadePipeline(
        RequestGateway     $requestGateway,
        VirtualHost        $host,
        TrustGatewayResult $proxy,
    ): GatewayFacade
    {
        return $this->registry->execute(Pipeline::Controller_ContextFacadeResolver,
            ControllerGatewayFacadeResolver::class,
            [$requestGateway, $host, $proxy]
        );
    }

    /**
     * Processes the request body using the specified pipeline and parser class.
     */
    public function requestBodyDecoderPipeline(
        RequestFacade $request,
        bool          $bodyDisabled,
        false|array   $allowFileUpload,
        int           $maxBodyBytes,
        int           $maxParams,
        int           $maxParamLength,
        int           $maxDepth,
        Buffer|string $body = null,
    ): null|Buffer|BatchEnvelope|array
    {
        return $this->registry->execute(Pipeline::Request_BodyDecoder,
            RequestBodyDecoder::class,
            [$request, $bodyDisabled, $allowFileUpload, $maxBodyBytes, $maxParams, $maxParamLength, $maxDepth, $body]
        );
    }

    /**
     * Encodes the response body using the specified pipeline and encoder class.
     */
    public function responseBodyEncoderPipeline(
        ContentType      $contentType,
        Charset          $charset,
        PayloadImmutable $response
    ): EncodedResponseBody
    {
        return $this->registry->execute(Pipeline::Response_BodyEncoder,
            ResponseBodyEncoder::class,
            [$contentType, $charset, $response]
        );
    }
}