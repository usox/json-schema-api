<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use Usox\JsonSchemaApi\Exception\ApiException;
use Usox\JsonSchemaApi\Exception\ApiMethodException;
use Usox\JsonSchemaApi\Exception\InternalException;
use Usox\JsonSchemaApi\Input\InputValidator;
use Usox\JsonSchemaApi\Input\InputValidatorInterface;
use JsonSchema\Validator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Teapot\StatusCode;
use Throwable;
use Usox\JsonSchemaApi\Input\MethodRetriever;
use Usox\JsonSchemaApi\Input\MethodRetrieverInterface;
use Usox\JsonSchemaApi\Input\MethodValidator;
use Usox\JsonSchemaApi\Response\ResponseBuilder;
use Usox\JsonSchemaApi\Response\ResponseBuilderInterface;

final class Endpoint
{
    private InputValidatorInterface $inputValidator;

    private MethodRetrieverInterface $methodRetriever;

    private ResponseBuilderInterface $responseBuilder;

    private UuidFactoryInterface $uuidFactory;

    private StreamFactoryInterface $streamFactory;

    private ?LoggerInterface $logger;

    public function __construct(
        InputValidatorInterface $inputValidator,
        MethodRetrieverInterface $methodRetriever,
        ResponseBuilderInterface $responseBuilder,
        UuidFactoryInterface $uuidFactory,
        StreamFactoryInterface $streamFactory,
        ?LoggerInterface $logger = null
    ) {
        $this->inputValidator = $inputValidator;
        $this->methodRetriever = $methodRetriever;
        $this->responseBuilder = $responseBuilder;
        $this->uuidFactory = $uuidFactory;
        $this->streamFactory = $streamFactory;
        $this->logger = $logger;
    }

    public function serve(
        RequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $statusCode = StatusCode::OK;
        
        try {
            $decodedInput = $this->inputValidator->validate($request);

            $handler = $this->methodRetriever->retrieve($decodedInput);

            // Process and build the response
            $responseData = $this->responseBuilder->buildResponse(
                $handler->handle($decodedInput->parameter)
            );
        } catch (ApiMethodException | ApiException $e) {
            $uuid = $this->uuidFactory->uuid4();
            
            $this->log($e, $uuid);
            
            // Build an error response
            $responseData = $this->responseBuilder->buildErrorResponse($e, $uuid);

            $statusCode = StatusCode::BAD_REQUEST;
        } catch (InternalException | Throwable $e) {
            $uuid = $this->uuidFactory->uuid4();
            
            $this->log($e, $uuid);
            
            $responseData = '';
            $statusCode = StatusCode::INTERNAL_SERVER_ERROR;
        } finally {
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($statusCode)
                ->withBody(
                    $this->streamFactory->createStream(json_encode($responseData))
                );
        }
    }
    
    private function log(
        Throwable $e,
        UuidInterface $uuid
    ): void {
        if ($this->logger !== null) {
            $this->logger->error(
                sprintf('%s (%d)', $e->getMessage(), $e->getCode()),
                [
                    'id' => $uuid->toString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );
        }
    }
    
    public static function factory(
        StreamFactoryInterface $streamFactory,
        ContainerInterface $container,
        ?LoggerInterface $logger = null
    ): Endpoint {
        $schemaValidator = new Validator();
        
        return new self(
            new InputValidator($schemaValidator),
            new MethodRetriever(
                new MethodValidator($schemaValidator),
                $container
            ),
            new ResponseBuilder(),
            new UuidFactory(),
            $streamFactory,
            $logger
        );
    }
}
