<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi;

use Http\Discovery\Psr17FactoryDiscovery;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use Teapot\StatusCode\Http;
use Throwable;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Dispatch\MethodDispatcher;
use Usox\JsonSchemaApi\Dispatch\MethodDispatcherInterface;
use Usox\JsonSchemaApi\Dispatch\MethodValidator;
use Usox\JsonSchemaApi\Dispatch\RequestValidator;
use Usox\JsonSchemaApi\Dispatch\RequestValidatorInterface;
use Usox\JsonSchemaApi\Dispatch\SchemaLoader;
use Usox\JsonSchemaApi\Exception\ApiException;
use Usox\JsonSchemaApi\Exception\InternalException;
use Usox\JsonSchemaApi\Response\ResponseBuilder;
use Usox\JsonSchemaApi\Response\ResponseBuilderInterface;

/**
 * @see Endpoint::factory()
 */
final readonly class Endpoint implements
    EndpointInterface
{
    public function __construct(
        private RequestValidatorInterface $inputValidator,
        private MethodDispatcherInterface $methodRetriever,
        private ResponseBuilderInterface $responseBuilder,
        private UuidFactoryInterface $uuidFactory,
        private StreamFactoryInterface $streamFactory,
        private ResponseFactoryInterface $responseFactory,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Execute the api handler and build the response
     */
    public function serve(
        ServerRequestInterface $request,
    ): ResponseInterface {
        $statusCode = Http::OK;
        $responseData = null;

        try {
            // Process and build the response
            $responseData = $this->responseBuilder->buildResponse(
                $this->methodRetriever->dispatch(
                    $request,
                    $this->inputValidator->validate($request),
                ),
            );
        } catch (ApiException $e) {
            $uuid = $this->uuidFactory->uuid4();

            $this->logError($e, $uuid);

            // Build an error response
            $responseData = $this->responseBuilder->buildErrorResponse($e, $uuid);

            $statusCode = Http::BAD_REQUEST;
        } catch (InternalException $e) {
            $this->logError(
                $e,
                $this->uuidFactory->uuid4(),
                $e->getContext(),
            );

            $statusCode = Http::INTERNAL_SERVER_ERROR;
        } catch (Throwable $e) {
            $this->logError(
                $e,
                $this->uuidFactory->uuid4(),
            );

            $statusCode = Http::INTERNAL_SERVER_ERROR;
        }

        $response = $this->responseFactory->createResponse($statusCode);

        if ($responseData !== null) {
            $response = $response->withBody(
                $this->streamFactory->createStream(
                    (string) json_encode($responseData),
                ),
            );
        }

        return $response
            ->withHeader('Content-Type', 'application/json')
        ;
    }

    /**
     * @param array<mixed, mixed> $context
     */
    private function logError(
        Throwable $e,
        UuidInterface $uuid,
        array $context = [],
    ): void {
        $this->logger?->error(
            sprintf('%s (%d)', $e->getMessage(), $e->getCode()),
            [
                'id' => $uuid->toString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'context' => $context,
            ],
        );
    }

    /**
     * Builds the endpoint.
     *
     * The factories may be omitted, the endpoint will try to autodetect existing PSR17 implementations
     */
    public static function factory(
        MethodProviderInterface $methodProvider,
        ?StreamFactoryInterface $streamFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?LoggerInterface $logger = null,
    ): EndpointInterface {
        $schemaValidator = new Validator();
        $schemaLoader = new SchemaLoader();

        if ($streamFactory === null) {
            $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        }

        if ($responseFactory === null) {
            $responseFactory = Psr17FactoryDiscovery::findResponseFactory();
        }

        return new self(
            new RequestValidator(
                $schemaLoader,
                $schemaValidator,
            ),
            new MethodDispatcher(
                $schemaLoader,
                new MethodValidator(
                    $schemaValidator,
                    new ErrorFormatter(),
                ),
                $methodProvider,
                $logger,
            ),
            new ResponseBuilder(),
            new UuidFactory(),
            $streamFactory,
            $responseFactory,
            $logger,
        );
    }

    public function handle(
        ServerRequestInterface $request,
    ): ResponseInterface {
        return $this->serve(
            $request,
        );
    }
}
