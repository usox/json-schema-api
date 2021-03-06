<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Exception\MethodNotFoundException;

class MethodDispatcherTest extends MockeryTestCase
{
    /** @var MockInterface|SchemaLoaderInterface */
    private MockInterface $schemaLoader;

    /** @var MethodValidatorInterface|MockInterface */
    private MockInterface $methodValidator;

    /** @var MethodProviderInterface|MockInterface */
    private MockInterface $methodProvider;

    private MethodDispatcher $subject;

    public function setUp(): void
    {
        $this->schemaLoader = Mockery::mock(SchemaLoaderInterface::class);
        $this->methodValidator = Mockery::mock(MethodValidatorInterface::class);
        $this->methodProvider = Mockery::mock(MethodProviderInterface::class);

        $this->subject = new MethodDispatcher(
            $this->schemaLoader,
            $this->methodValidator,
            $this->methodProvider
        );
    }

    public function testDispatchThrowsExceptionIfMethodDoesNotExist(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);

        $this->expectException(MethodNotFoundException::class);
        $this->expectExceptionMessage('Method not found');
        $this->expectExceptionCode(StatusCode::BAD_REQUEST);

        $method = 'some-method';
        $version = null;

        $input = ['method' => $method, 'version' => $version];

        $this->methodProvider->shouldReceive('lookup')
            ->with($method)
            ->once()
            ->andReturnNull();

        $this->subject->dispatch(
            $request,
            (object) $input
        );
    }

    public function testDispatchReturnsHandler(): void
    {
        $method = 'some-method';
        $version = 666;
        $result = ['some-result'];
        $parameter = (object) ['some-parameter'];
        $schemaContent = (object) ['some' => 'schema-content'];
        $schemaFilePath = 'some-path';

        $input = (object) ['method' => $method, 'version' => $version, 'parameter' => $parameter];

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
            ->with(sprintf('%s.%d', $method, $version))
            ->once()
            ->andReturn($handler);

        $this->methodValidator->shouldReceive('validateInput')
            ->with($schemaContent, $input)
            ->once();
        $this->methodValidator->shouldReceive('validateOutput')
            ->with(
                $schemaContent,
                Mockery::on(function ($param) use ($result): bool {
                    return (array) $param == $result;
                })
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
