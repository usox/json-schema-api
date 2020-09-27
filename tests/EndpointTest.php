<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi;

use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Exception\ApiException;
use Usox\JsonSchemaApi\Exception\ApiMethodException;
use Usox\JsonSchemaApi\Input\InputValidatorInterface;
use Usox\JsonSchemaApi\Input\MethodRetrieverInterface;
use Usox\JsonSchemaApi\Response\ResponseBuilderInterface;

class EndpointTest extends MockeryTestCase
{
    /** @var InputValidatorInterface|MockInterface|null */
    private MockInterface $inputValidator;

    /** @var MethodRetrieverInterface|MockInterface|null */
    private MockInterface $methodRetriever;

    /** @var ResponseBuilderInterface|MockInterface|null */
    private MockInterface $responseBuilder;

    /** @var UuidFactoryInterface|MockInterface|null */
    private MockInterface $uuidFactory;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;
    
    /** @var Endpoint|null */
    private Endpoint $subject;
    
    public function setUp(): void
    {
        $this->inputValidator = Mockery::mock(InputValidatorInterface::class);
        $this->methodRetriever = Mockery::mock(MethodRetrieverInterface::class);
        $this->responseBuilder = Mockery::mock(ResponseBuilderInterface::class);
        $this->uuidFactory = Mockery::mock(UuidFactoryInterface::class);
        $this->streamFactory = Mockery::mock(StreamFactoryInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        
        $this->subject = new Endpoint(
            $this->inputValidator,
            $this->methodRetriever,
            $this->responseBuilder,
            $this->uuidFactory,
            $this->streamFactory,
            $this->logger
        );
    }
    
    public function testServeReturnsHandlerOutput(): void
    {
        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $handler = Mockery::mock(ApiMethodInterface::class);
        
        $parameter = new stdClass();
        $decodedInput = new stdClass();
        $decodedInput->parameter = $parameter;
        $responseData = ['some-response'];
        $processedResponse = ['some-processed-response'];
        
        $this->inputValidator->shouldReceive('validate')
            ->with($request)
            ->once()
            ->andReturn($decodedInput);
        
        $this->methodRetriever->shouldReceive('retrieve')
            ->with($decodedInput)
            ->once()
            ->andReturn($handler);
        
        $handler->shouldReceive('handle')
            ->with($parameter)
            ->once()
            ->andReturn($responseData);
        
        $this->responseBuilder->shouldReceive('buildResponse')
            ->with($responseData)
            ->once()
            ->andReturn($processedResponse);
        
        $this->createResponseExpectations(
            $response,
            $processedResponse,
            StatusCode::OK
        );
        
        $this->assertSame(
            $response,
            $this->subject->serve($request, $response)
        );
    }

    public function testServeCatchesApiMethodException(): void
    {
        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $uuid = Mockery::mock(UuidInterface::class);
        
        $errorMessage = 'some-error';
        $errorCode = 666;
        $processedResponse = ['some-processed-response'];
        $uuidValue = 'some-uuid';

        $error = new class($errorMessage, $errorCode) extends ApiMethodException {};

        $this->inputValidator->shouldReceive('validate')
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

        $this->createResponseExpectations(
            $response,
            $processedResponse,
            StatusCode::BAD_REQUEST
        );
        
        $this->logger->shouldReceive('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                Mockery::type('array')
            )
            ->once();

        $this->assertSame(
            $response,
            $this->subject->serve($request, $response)
        );
    }

    public function testServeCatchesApiException(): void
    {
        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $uuid = Mockery::mock(UuidInterface::class);

        $errorMessage = 'some-error';
        $errorCode = 666;
        $processedResponse = ['some-processed-response'];
        $uuidValue = 'some-uuid';

        $error = new class($errorMessage, $errorCode) extends ApiException {};

        $this->inputValidator->shouldReceive('validate')
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

        $this->createResponseExpectations(
            $response,
            $processedResponse,
            StatusCode::BAD_REQUEST
        );

        $this->logger->shouldReceive('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                Mockery::type('array')
            )
            ->once();

        $this->assertSame(
            $response,
            $this->subject->serve($request, $response)
        );
    }

    public function testServeCatchesGenericException(): void
    {
        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $uuid = Mockery::mock(UuidInterface::class);

        $errorMessage = 'some-error';
        $errorCode = 666;
        $uuidValue = 'some-uuid';

        $error = new Exception($errorMessage, $errorCode);

        $this->inputValidator->shouldReceive('validate')
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

        $this->createResponseExpectations(
            $response,
            '',
            StatusCode::INTERNAL_SERVER_ERROR
        );

        $this->logger->shouldReceive('error')
            ->with(
                sprintf('%s (%d)', $errorMessage, $errorCode),
                Mockery::type('array')
            )
            ->once();

        $this->assertSame(
            $response,
            $this->subject->serve($request, $response)
        );
    }
    
    public function testFactoryReturnsInstance(): void
    {
        $this->assertInstanceOf(
            Endpoint::class,
            Endpoint::factory(
                Mockery::mock(StreamFactoryInterface::class),
                Mockery::mock(ContainerInterface::class)
            )
        );
    }

    private function createResponseExpectations(
        MockInterface $response,
        $responseData,
        int $statusCode
    ): void {
        $stream = Mockery::mock(StreamInterface::class);
        
        $this->streamFactory->shouldReceive('createStream')
            ->with(json_encode($responseData))
            ->once()
            ->andReturn($stream);
        
        $response->shouldReceive('withHeader')
            ->with('Content-Type', 'application/json')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withStatus')
            ->with($statusCode)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();
    }
}
