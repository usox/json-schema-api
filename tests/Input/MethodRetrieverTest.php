<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Input;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Exception\MethodNotFoundException;

class MethodRetrieverTest extends MockeryTestCase
{
    /** @var MethodValidatorInterface|MockInterface|null */
    private MockInterface $methodValidator;

    /** @var MethodProviderInterface|MockInterface|null */
    private MockInterface $methodProvider;
    
    /** @var MethodRetriever|null */
    private MethodRetriever $subject;
    
    public function setUp(): void
    {
        $this->methodValidator = Mockery::mock(MethodValidatorInterface::class);
        $this->methodProvider = Mockery::mock(MethodProviderInterface::class);
        
        $this->subject = new MethodRetriever(
            $this->methodValidator,
            $this->methodProvider
        );
    }
    
    public function testRetrieveThrowsExceptionIfMethodDoesNotExist(): void
    {
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
        
        $this->subject->retrieve((object) $input);
    }

    public function testRetrieveReturnsHandler(): void
    {
        $method = 'some-method';
        $version = 666;

        $input = (object) ['method' => $method, 'version' => $version];
        
        $handler = Mockery::mock(ApiMethodInterface::class);

        $this->methodProvider->shouldReceive('lookup')
            ->with(sprintf('%s.%d', $method, $version))
            ->once()
            ->andReturn($handler);
        
        $this->methodValidator->shouldReceive('validate')
            ->with($handler, $input)
            ->once();

        $this->assertSame(
            $handler,
            $this->subject->retrieve($input)
        );
    }
}
