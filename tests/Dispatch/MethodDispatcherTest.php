<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Teapot\StatusCode\Http;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Exception\MethodNotFoundException;

class MethodDispatcherTest extends TestCase
{
    private MockObject&SchemaLoaderInterface $schemaLoader;

    private MockObject&MethodValidatorInterface $methodValidator;

    private MockObject&MethodProviderInterface $methodProvider;

    private MockObject&LoggerInterface $logger;

    private MethodDispatcher $subject;

    protected function setUp(): void
    {
        $this->schemaLoader = $this->createMock(SchemaLoaderInterface::class);
        $this->methodValidator = $this->createMock(MethodValidatorInterface::class);
        $this->methodProvider = $this->createMock(MethodProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new MethodDispatcher(
            $this->schemaLoader,
            $this->methodValidator,
            $this->methodProvider,
            $this->logger,
        );
    }

    public function testDispatchThrowsExceptionIfMethodDoesNotExist(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $this->expectException(MethodNotFoundException::class);
        $this->expectExceptionMessage('Method not found');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $method = 'some-method';
        $parameter = (object) ['some-parameter'];

        $input = ['method' => $method, 'parameter' => $parameter];

        $this->methodProvider->expects(static::once())
            ->method('lookup')
            ->with($method)
            ->willReturn(null);

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                'Api method call',
                [
                    'method' => $method,
                    'input' => $parameter,
                ],
            );

        $this->subject->dispatch(
            $request,
            (object) $input,
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

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(ApiMethodInterface::class);

        $handler->expects(static::once())
            ->method('getSchemaFile')
            ->willReturn($schemaFilePath);

        $this->schemaLoader->expects(static::once())
            ->method('load')
            ->with($schemaFilePath)
            ->willReturn($schemaContent);

        $this->methodProvider->expects(static::once())
            ->method('lookup')
            ->with($method)
            ->willReturn($handler);

        $this->methodValidator->expects(static::once())
            ->method('validateInput')
            ->with($schemaContent, $input);
        $this->methodValidator->expects(static::once())
            ->method('validateOutput')
            ->with(
                $schemaContent,
                $this->callback(static fn ($param): bool => (array) $param == $result),
            );

        $this->logger->expects(static::once())
            ->method('info')
            ->with(
                'Api method call',
                [
                    'method' => $method,
                ],
            );
        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                'Api method call',
                [
                    'method' => $method,
                    'input' => $input->parameter,
                ],
            );

        $handler->expects(static::once())
            ->method('handle')
            ->with($request, $parameter)
            ->willReturn($result);

        static::assertSame(
            $result,
            $this->subject->dispatch($request, $input),
        );
    }
}
