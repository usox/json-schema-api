<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

class EndpointTest extends TestCase
{
    private MockObject&RequestValidatorInterface $requestValidator;

    private MockObject&MethodDispatcherInterface $methodDispatcher;

    private MockObject&ResponseBuilderInterface $responseBuilder;

    private MockObject&UuidFactoryInterface $uuidFactory;

    private MockObject&StreamFactoryInterface $streamFactory;

    private MockObject&ResponseFactoryInterface $responseFactory;

    private MockObject&LoggerInterface $logger;

    private Endpoint $subject;

    protected function setUp(): void
    {
        $this->requestValidator = $this->createMock(RequestValidatorInterface::class);
        $this->methodDispatcher = $this->createMock(MethodDispatcherInterface::class);
        $this->responseBuilder = $this->createMock(ResponseBuilderInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new Endpoint(
            $this->requestValidator,
            $this->methodDispatcher,
            $this->responseBuilder,
            $this->uuidFactory,
            $this->streamFactory,
            $this->responseFactory,
            $this->logger,
        );
    }

    public function testServeReturnsHandlerOutput(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $parameter = new stdClass();
        $decodedInput = new stdClass();
        $decodedInput->parameter = $parameter;
        $responseData = ['some-response'];
        $processedResponse = ['some-processed-response'];

        $this->requestValidator->expects(static::once())
            ->method('validate')
            ->with($request)
            ->willReturn($decodedInput);

        $this->methodDispatcher->expects(static::once())
            ->method('dispatch')
            ->with($request, $decodedInput)
            ->willReturn($responseData);

        $this->responseBuilder->expects(static::once())
            ->method('buildResponse')
            ->with($responseData)
            ->willReturn($processedResponse);

        $this->responseFactory->expects(static::once())
            ->method('createResponse')
            ->with(Http::OK)
            ->willReturn($response);

        $this->createResponseExpectations(
            $response,
            $processedResponse,
        );

        static::assertSame(
            $response,
            $this->subject->serve($request),
        );
    }

    public function testServeCatchesApiMethodException(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uuid = $this->createMock(UuidInterface::class);

        $errorMessage = 'some-error';
        $errorCode = 666;
        $processedResponse = ['some-processed-response'];
        $uuidValue = 'some-uuid';

        $error = new class ($errorMessage, $errorCode) extends ApiMethodException {
        };

        $this->requestValidator->expects(static::once())
            ->method('validate')
            ->with($request)
            ->willThrowException($error);

        $this->responseBuilder->expects(static::once())
            ->method('buildErrorResponse')
            ->with($error, $uuid)
            ->willReturn($processedResponse);

        $this->uuidFactory->expects(static::once())
            ->method('uuid4')
            ->willReturn($uuid);

        $uuid->expects(static::once())
            ->method('toString')
            ->willReturn($uuidValue);

        $this->responseFactory->expects(static::once())
            ->method('createResponse')
            ->with(Http::BAD_REQUEST)
            ->willReturn($response);

        $this->createResponseExpectations(
            $response,
            $processedResponse,
        );

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                self::isType('array'),
            );

        static::assertSame(
            $response,
            $this->subject->serve($request),
        );
    }

    public function testServeCatchesApiException(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uuid = $this->createMock(UuidInterface::class);

        $errorMessage = 'some-error';
        $errorCode = 666;
        $processedResponse = ['some-processed-response'];
        $uuidValue = 'some-uuid';

        $error = new class ($errorMessage, $errorCode) extends ApiException {
        };

        $this->requestValidator->expects(static::once())
            ->method('validate')
            ->with($request)
            ->willThrowException($error);

        $this->responseBuilder->expects(static::once())
            ->method('buildErrorResponse')
            ->with($error, $uuid)
            ->willReturn($processedResponse);

        $this->uuidFactory->expects(static::once())
            ->method('uuid4')
            ->willReturn($uuid);

        $uuid->expects(static::once())
            ->method('toString')
            ->willReturn($uuidValue);

        $this->responseFactory->expects(static::once())
            ->method('createResponse')
            ->with(Http::BAD_REQUEST)
            ->willReturn($response);

        $this->createResponseExpectations(
            $response,
            $processedResponse,
        );

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                static::isType('array'),
            );

        static::assertSame(
            $response,
            $this->subject->serve($request),
        );
    }

    public function testServeCatchesGenericException(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uuid = $this->createMock(UuidInterface::class);

        $errorMessage = 'some-error';
        $errorCode = 666;
        $uuidValue = 'some-uuid';

        $error = new Exception($errorMessage, $errorCode);

        $this->requestValidator->expects(static::once())
            ->method('validate')
            ->with($request)
            ->willThrowException($error);

        $this->uuidFactory->expects(static::once())
            ->method('uuid4')
            ->willReturn($uuid);

        $uuid->expects(static::once())
            ->method('toString')
            ->willReturn($uuidValue);

        $this->responseFactory->expects(static::once())
            ->method('createResponse')
            ->with(Http::INTERNAL_SERVER_ERROR)
            ->willReturn($response);

        $this->createResponseExpectations(
            $response,
            null,
        );

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                static::isType('array'),
            );

        static::assertSame(
            $response,
            $this->subject->serve($request),
        );
    }

    public function testServeCatchesInternalException(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $uuid = $this->createMock(UuidInterface::class);

        $errorMessage = 'some-error';
        $errorCode = 666;
        $uuidValue = 'some-uuid';
        $context = ['some' => 'context'];

        $error = new ResponseMalformedException($errorMessage, $errorCode, null, $context);

        $this->requestValidator->expects(static::once())
            ->method('validate')
            ->with($request)
            ->willThrowException($error);

        $this->uuidFactory->expects(static::once())
            ->method('uuid4')
            ->willReturn($uuid);

        $uuid->expects(static::once())
            ->method('toString')
            ->willReturn($uuidValue);

        $this->responseFactory->expects(static::once())
            ->method('createResponse')
            ->with(Http::INTERNAL_SERVER_ERROR)
            ->willReturn($response);

        $this->createResponseExpectations(
            $response,
            null,
        );

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                static::isType('array'),
            );

        static::assertSame(
            $response,
            $this->subject->serve($request),
        );
    }

    public function testFactoryReturnsInstance(): void
    {
        static::assertInstanceOf(
            Endpoint::class,
            Endpoint::factory(
                $this->createMock(MethodProviderInterface::class),
                $this->createMock(StreamFactoryInterface::class),
                $this->createMock(ResponseFactoryInterface::class),
            ),
        );
    }

    public function testFactoryReturnsInstanceUsingAutoDetection(): void
    {
        static::assertInstanceOf(
            Endpoint::class,
            Endpoint::factory(
                $this->createMock(MethodProviderInterface::class),
            ),
        );
    }

    public function testHandleWorks(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $parameter = new stdClass();
        $decodedInput = new stdClass();
        $decodedInput->parameter = $parameter;
        $responseData = ['some-response'];
        $processedResponse = ['some-processed-response'];

        $this->requestValidator->expects(static::once())
            ->method('validate')
            ->with($request)
            ->willReturn($decodedInput);

        $this->methodDispatcher->expects(static::once())
            ->method('dispatch')
            ->with($request, $decodedInput)
            ->willReturn($responseData);

        $this->responseBuilder->expects(static::once())
            ->method('buildResponse')
            ->with($responseData)
            ->willReturn($processedResponse);

        $this->responseFactory->expects(static::once())
            ->method('createResponse')
            ->with(Http::OK)
            ->willReturn($response);

        $this->createResponseExpectations(
            $response,
            $processedResponse,
        );

        static::assertSame(
            $response,
            $this->subject->handle($request),
        );
    }

    private function createResponseExpectations(
        MockObject&ResponseInterface $response,
        string|array|null $responseData,
    ): void {
        if ($responseData !== null) {
            $stream = $this->createMock(StreamInterface::class);

            $this->streamFactory->expects(static::once())
                ->method('createStream')
                ->with(json_encode($responseData))
                ->willReturn($stream);

            $response->expects(static::once())
                ->method('withBody')
                ->with($stream)
                ->willReturnSelf();
        }

        $response->expects(static::once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
    }
}
