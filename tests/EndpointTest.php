<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi;

use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use stdClass;
use Teapot\StatusCode\Http;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Dispatch\MethodDispatcherInterface;
use Usox\JsonSchemaApi\Dispatch\RequestValidatorInterface;
use Usox\JsonSchemaApi\Exception\ApiException;
use Usox\JsonSchemaApi\Exception\ApiMethodException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;
use Usox\JsonSchemaApi\Response\ResponseBuilderInterface;

class EndpointTest extends MockeryTestCase
{
    private MockInterface&RequestValidatorInterface $requestValidator;

    private MockInterface&MethodDispatcherInterface $methodDispatcher;

    private MockInterface&ResponseBuilderInterface $responseBuilder;

    private MockInterface&UuidFactoryInterface $uuidFactory;

    private MockInterface&StreamFactoryInterface $streamFactory;

    private MockInterface&ResponseFactoryInterface $responseFactory;

    private MockInterface&LoggerInterface $logger;

    private Endpoint $subject;

    public function setUp(): void
    {
        $this->requestValidator = Mockery::mock(RequestValidatorInterface::class);
        $this->methodDispatcher = Mockery::mock(MethodDispatcherInterface::class);
        $this->responseBuilder = Mockery::mock(ResponseBuilderInterface::class);
        $this->uuidFactory = Mockery::mock(UuidFactoryInterface::class);
        $this->streamFactory = Mockery::mock(StreamFactoryInterface::class);
        $this->responseFactory = Mockery::mock(ResponseFactoryInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->subject = new Endpoint(
            $this->requestValidator,
            $this->methodDispatcher,
            $this->responseBuilder,
            $this->uuidFactory,
            $this->streamFactory,
            $this->responseFactory,
            $this->logger
        );
    }

    public function testServeReturnsHandlerOutput(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $parameter = new stdClass();
        $decodedInput = new stdClass();
        $decodedInput->parameter = $parameter;
        $responseData = ['some-response'];
        $processedResponse = ['some-processed-response'];

        $this->requestValidator->shouldReceive('validate')
            ->with($request)
            ->once()
            ->andReturn($decodedInput);

        $this->methodDispatcher->shouldReceive('dispatch')
            ->with($request, $decodedInput)
            ->once()
            ->andReturn($responseData);

        $this->responseBuilder->shouldReceive('buildResponse')
            ->with($responseData)
            ->once()
            ->andReturn($processedResponse);

        $this->responseFactory->shouldReceive('createResponse')
            ->with(Http::OK)
            ->once()
            ->andReturn($response);

        $this->createResponseExpectations(
            $response,
            $processedResponse,
        );

        static::assertSame(
            $response,
            $this->subject->serve($request)
        );
    }

    public function testServeCatchesApiMethodException(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $uuid = Mockery::mock(UuidInterface::class);

        $errorMessage = 'some-error';
        $errorCode = 666;
        $processedResponse = ['some-processed-response'];
        $uuidValue = 'some-uuid';

        $error = new class ($errorMessage, $errorCode) extends ApiMethodException {
        };

        $this->requestValidator->shouldReceive('validate')
            ->with($request)
            ->once()
            ->andThrow($error);

        $this->responseBuilder->shouldReceive('buildErrorResponse')
            ->with($error, $uuid)
            ->once()
            ->andReturn($processedResponse);

        $this->uuidFactory->shouldReceive('uuid4')
            ->withNoArgs()
            ->once()
            ->andReturn($uuid);

        $uuid->shouldReceive('toString')
            ->withNoArgs()
            ->once()
            ->andReturn($uuidValue);

        $this->responseFactory->shouldReceive('createResponse')
            ->with(Http::BAD_REQUEST)
            ->once()
            ->andReturn($response);

        $this->createResponseExpectations(
            $response,
            $processedResponse,
        );

        $this->logger->shouldReceive('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                Mockery::type('array')
            )
            ->once();

        static::assertSame(
            $response,
            $this->subject->serve($request)
        );
    }

    public function testServeCatchesApiException(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $uuid = Mockery::mock(UuidInterface::class);

        $errorMessage = 'some-error';
        $errorCode = 666;
        $processedResponse = ['some-processed-response'];
        $uuidValue = 'some-uuid';

        $error = new class ($errorMessage, $errorCode) extends ApiException {
        };

        $this->requestValidator->shouldReceive('validate')
            ->with($request)
            ->once()
            ->andThrow($error);

        $this->responseBuilder->shouldReceive('buildErrorResponse')
            ->with($error, $uuid)
            ->once()
            ->andReturn($processedResponse);

        $this->uuidFactory->shouldReceive('uuid4')
            ->withNoArgs()
            ->once()
            ->andReturn($uuid);

        $uuid->shouldReceive('toString')
            ->withNoArgs()
            ->once()
            ->andReturn($uuidValue);

        $this->responseFactory->shouldReceive('createResponse')
            ->with(Http::BAD_REQUEST)
            ->once()
            ->andReturn($response);

        $this->createResponseExpectations(
            $response,
            $processedResponse,
        );

        $this->logger->shouldReceive('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                Mockery::type('array')
            )
            ->once();

        static::assertSame(
            $response,
            $this->subject->serve($request)
        );
    }

    public function testServeCatchesGenericException(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $uuid = Mockery::mock(UuidInterface::class);

        $errorMessage = 'some-error';
        $errorCode = 666;
        $uuidValue = 'some-uuid';

        $error = new Exception($errorMessage, $errorCode);

        $this->requestValidator->shouldReceive('validate')
            ->with($request)
            ->once()
            ->andThrow($error);

        $this->uuidFactory->shouldReceive('uuid4')
            ->withNoArgs()
            ->once()
            ->andReturn($uuid);

        $uuid->shouldReceive('toString')
            ->withNoArgs()
            ->once()
            ->andReturn($uuidValue);

        $this->responseFactory->shouldReceive('createResponse')
            ->with(Http::INTERNAL_SERVER_ERROR)
            ->once()
            ->andReturn($response);

        $this->createResponseExpectations(
            $response,
            null,
        );

        $this->logger->shouldReceive('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                Mockery::type('array')
            )
            ->once();

        static::assertSame(
            $response,
            $this->subject->serve($request)
        );
    }

    public function testServeCatchesInternalException(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $uuid = Mockery::mock(UuidInterface::class);

        $errorMessage = 'some-error';
        $errorCode = 666;
        $uuidValue = 'some-uuid';
        $context = ['some' => 'context'];

        $error = new ResponseMalformedException($errorMessage, $errorCode, null, $context);

        $this->requestValidator->shouldReceive('validate')
            ->with($request)
            ->once()
            ->andThrow($error);

        $this->uuidFactory->shouldReceive('uuid4')
            ->withNoArgs()
            ->once()
            ->andReturn($uuid);

        $uuid->shouldReceive('toString')
            ->withNoArgs()
            ->once()
            ->andReturn($uuidValue);

        $this->responseFactory->shouldReceive('createResponse')
            ->with(Http::INTERNAL_SERVER_ERROR)
            ->once()
            ->andReturn($response);

        $this->createResponseExpectations(
            $response,
            null,
        );

        $this->logger->shouldReceive('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                Mockery::type('array')
            )
            ->once();

        static::assertSame(
            $response,
            $this->subject->serve($request)
        );
    }

    public function testFactoryReturnsInstance(): void
    {
        static::assertInstanceOf(
            Endpoint::class,
            Endpoint::factory(
                Mockery::mock(MethodProviderInterface::class),
                Mockery::mock(StreamFactoryInterface::class),
                Mockery::mock(ResponseFactoryInterface::class),
            )
        );
    }

    public function testFactoryReturnsInstanceUsingAutoDetection(): void
    {
        static::assertInstanceOf(
            Endpoint::class,
            Endpoint::factory(
                Mockery::mock(MethodProviderInterface::class),
            )
        );
    }

    public function testHandleWorks(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $parameter = new stdClass();
        $decodedInput = new stdClass();
        $decodedInput->parameter = $parameter;
        $responseData = ['some-response'];
        $processedResponse = ['some-processed-response'];

        $this->requestValidator->shouldReceive('validate')
            ->with($request)
            ->once()
            ->andReturn($decodedInput);

        $this->methodDispatcher->shouldReceive('dispatch')
            ->with($request, $decodedInput)
            ->once()
            ->andReturn($responseData);

        $this->responseBuilder->shouldReceive('buildResponse')
            ->with($responseData)
            ->once()
            ->andReturn($processedResponse);

        $this->responseFactory->shouldReceive('createResponse')
            ->with(Http::OK)
            ->once()
            ->andReturn($response);

        $this->createResponseExpectations(
            $response,
            $processedResponse,
        );

        static::assertSame(
            $response,
            $this->subject->handle($request)
        );
    }

    private function createResponseExpectations(
        MockInterface $response,
        string|array|null $responseData,
    ): void {
        if ($responseData !== null) {
            $stream = Mockery::mock(StreamInterface::class);

            $this->streamFactory->shouldReceive('createStream')
                ->with(json_encode($responseData))
                ->once()
                ->andReturn($stream);

            $response->shouldReceive('withBody')
                ->with($stream)
                ->once()
                ->andReturnSelf();
        }

        $response->shouldReceive('withHeader')
            ->with('Content-Type', 'application/json')
            ->once()
            ->andReturnSelf();
    }
}
