<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Teapot\StatusCode\Http;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Exception\MethodNotFoundException;

class MethodDispatcherTest extends MockeryTestCase
{
    private MockInterface&SchemaLoaderInterface $schemaLoader;

    private MockInterface&MethodValidatorInterface $methodValidator;

    private MockInterface&MethodProviderInterface $methodProvider;

    private MockInterface&LoggerInterface $logger;

    private MethodDispatcher $subject;

    protected function setUp(): void
    {
        $this->schemaLoader = Mockery::mock(SchemaLoaderInterface::class);
        $this->methodValidator = Mockery::mock(MethodValidatorInterface::class);
        $this->methodProvider = Mockery::mock(MethodProviderInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->subject = new MethodDispatcher(
            $this->schemaLoader,
            $this->methodValidator,
            $this->methodProvider,
            $this->logger
        );
    }

    public function testDispatchThrowsExceptionIfMethodDoesNotExist(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);

        $this->expectException(MethodNotFoundException::class);
        $this->expectExceptionMessage('Method not found');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $method = 'some-method';
        $parameter = (object) ['some-parameter'];

        $input = ['method' => $method, 'parameter' => $parameter];

        $this->methodProvider->shouldReceive('lookup')
            ->with($method)
            ->once()
            ->andReturnNull();

        $this->logger->shouldReceive('debug')
            ->with(
                'Api method call',
                [
                    'method' => $method,
                    'input' => $parameter
                ]
            )
            ->once();

        $this->subject->dispatch(
            $request,
            (object) $input
        );
    }

    public function testDispatchReturnsHandler(): void
    {
        $method = 'some-method';
        $result = ['some-result'];
        $parameter = (object) ['some-parameter'];
        $schemaContent = (object) ['some' => 'schema-content'];
        $schemaFilePath = 'some-path';

        $input = (object) ['method' => $method, 'parameter' => $parameter];

        $request = Mockery::mock(ServerRequestInterface::class);
        $handler = Mockery::mock(ApiMethodInterface::class);

        $handler->shouldReceive('getSchemaFile')
            ->withNoArgs()
            ->once()
            ->andReturn($schemaFilePath);

        $this->schemaLoader->shouldReceive('load')
            ->with($schemaFilePath)
            ->once()
            ->andReturn($schemaContent);

        $this->methodProvider->shouldReceive('lookup')
            ->with($method)
            ->once()
            ->andReturn($handler);

        $this->methodValidator->shouldReceive('validateInput')
            ->with($schemaContent, $input)
            ->once();
        $this->methodValidator->shouldReceive('validateOutput')
            ->with(
                $schemaContent,
                Mockery::on(static fn ($param): bool => (array) $param == $result)
            )
            ->once();

        $this->logger->shouldReceive('info')
            ->with(
                'Api method call',
                [
                    'method' => $method,
                ]
            )
            ->once();
        $this->logger->shouldReceive('debug')
            ->with(
                'Api method call',
                [
                    'method' => $method,
                    'input' => $input->parameter
                ]
            )
            ->once();

        $handler->shouldReceive('handle')
            ->with($request, $parameter)
            ->once()
            ->andReturn($result);

        static::assertSame(
            $result,
            $this->subject->dispatch($request, $input)
        );
    }
}
