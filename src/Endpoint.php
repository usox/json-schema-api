<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi;

use Http\Discovery\Psr17FactoryDiscovery;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use Teapot\StatusCode;
use Throwable;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Exception\ApiException;
use Usox\JsonSchemaApi\Exception\InternalException;
use Usox\JsonSchemaApi\Dispatch\MethodDispatcher;
use Usox\JsonSchemaApi\Dispatch\MethodDispatcherInterface;
use Usox\JsonSchemaApi\Dispatch\MethodValidator;
use Usox\JsonSchemaApi\Dispatch\RequestValidator;
use Usox\JsonSchemaApi\Dispatch\RequestValidatorInterface;
use Usox\JsonSchemaApi\Dispatch\SchemaLoader;
use Usox\JsonSchemaApi\Response\ResponseBuilder;
use Usox\JsonSchemaApi\Response\ResponseBuilderInterface;

final class Endpoint implements
    EndpointInterface
{
    public function __construct(
        private RequestValidatorInterface $inputValidator,
        private MethodDispatcherInterface $methodRetriever,
        private ResponseBuilderInterface $responseBuilder,
        private UuidFactoryInterface $uuidFactory,
        private StreamFactoryInterface $streamFactory,
        private ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Try to execute the api handler and build the response
     */
    public function serve(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $statusCode = StatusCode::OK;
        $responseData = null;

        try {
            // Process and build the response
            $responseData = $this->responseBuilder->buildResponse(
                $this->methodRetriever->dispatch(
                    $request,
                    $this->inputValidator->validate($request)
                )
            );
        } catch (ApiException $e) {
            $uuid = $this->uuidFactory->uuid4();

            $this->logError($e, $uuid);

            // Build an error response
            $responseData = $this->responseBuilder->buildErrorResponse($e, $uuid);

            $statusCode = StatusCode::BAD_REQUEST;
        } catch (InternalException $e) {
            $this->logError(
                $e,
                $this->uuidFactory->uuid4(),
                $e->getContext()
            );

            $statusCode = StatusCode::INTERNAL_SERVER_ERROR;
        } catch (Throwable $e) {
            $this->logError(
                $e,
                $this->uuidFactory->uuid4()
            );

            $statusCode = StatusCode::INTERNAL_SERVER_ERROR;
        }

        if ($responseData !== null) {
            $response = $response->withBody(
                $this->streamFactory->createStream(
                    (string) json_encode($responseData)
                )
            );
        }

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * @param array<mixed, mixed> $context
     */
    private function logError(
        Throwable $e,
        UuidInterface $uuid,
        array $context = []
    ): void {
        $this->logger?->error(
            sprintf('%s (%d)', $e->getMessage(), $e->getCode()),
            [
                'id' => $uuid->toString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'context' => $context
            ]
        );
    }

    /**
     * Builds the endpoint.
     * The StreamFactory can be omitted, the endpoint will search
     * for any existing PSR17 implementations
     */
    public static function factory(
        MethodProviderInterface $methodProvider,
        ?StreamFactoryInterface $streamFactory = null,
        ?LoggerInterface $logger = null
    ): EndpointInterface {
        $schemaValidator = new Validator();
        $schemaLoader = new SchemaLoader();

        if ($streamFactory === null) {
            $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        }

        return new self(
            new RequestValidator(
                $schemaLoader,
                $schemaValidator
            ),
            new MethodDispatcher(
                $schemaLoader,
                new MethodValidator(
                    $schemaValidator,
                    new ErrorFormatter()
                ),
                $methodProvider,
                $logger,
            ),
            new ResponseBuilder(),
            new UuidFactory(),
            $streamFactory,
            $logger
        );
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->serve(
            $request,
            $handler->handle($request)
        );
    }
}
