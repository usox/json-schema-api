<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Input;

use JsonSchema\Validator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Exception\JsonInvalidException;
use Usox\JsonSchemaApi\Exception\RequestValidationException;

class InputValidatorTest extends MockeryTestCase
{
    /** @var MockInterface|Validator|null */
    private MockInterface $validator;
    
    /** @var InputValidator|null */
    private InputValidator $subject;
    
    public function setUp(): void
    {
        $this->validator = Mockery::mock(Validator::class);
        
        $this->subject = new InputValidator(
            $this->validator
        );
    }
    
    public function testValidateThrowsExceptionIfInputIsInvalid(): void
    {
        $this->expectException(JsonInvalidException::class);
        $this->expectExceptionMessage('Input is no valid json (Syntax error)');
        $this->expectExceptionCode(StatusCode::BAD_REQUEST);
        
        $stream = Mockery::mock(StreamInterface::class);
        $request = Mockery::mock(ServerRequestInterface::class);
        
        $input = 'some-input' . PHP_EOL . 'errors';
        
        $request->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        
        $stream->shouldReceive('getContents')
            ->withNoArgs()
            ->once()
            ->andReturn($input);
        
        $this->subject->validate($request);
    }

    public function testValidateThrowsExceptionIfInputDoesNotValidate(): void
    {
        $this->expectException(RequestValidationException::class);
        $this->expectExceptionMessage('Request is invalid');
        $this->expectExceptionCode(StatusCode::BAD_REQUEST);

        $stream = Mockery::mock(StreamInterface::class);
        $request = Mockery::mock(ServerRequestInterface::class);

        $input = ['some' => 'input'];
        $schemaContent = json_decode(
            file_get_contents(__DIR__ . '/../../dist/request.json')
        );
        
        $this->validator->shouldReceive('validate')
            ->with(
                Mockery::on(static function ($value) use ($input): bool {
                    return (array) $value === $input;
                }),
                Mockery::on(static function ($value) use ($schemaContent): bool {
                    return $value == $schemaContent;
                })
            )
            ->once()
            ->andReturn(666);

        $request->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);

        $stream->shouldReceive('getContents')
            ->withNoArgs()
            ->once()
            ->andReturn(json_encode($input));

        $this->subject->validate($request);
    }

    public function testValidateReturnsValidatedInput(): void
    {
        $stream = Mockery::mock(StreamInterface::class);
        $request = Mockery::mock(ServerRequestInterface::class);

        $input = ['some' => 'input'];
        $schemaContent = json_decode(
            file_get_contents(__DIR__ . '/../../dist/request.json')
        );

        $this->validator->shouldReceive('validate')
            ->with(
                Mockery::on(static function ($value) use ($input): bool {
                    return (array) $value === $input;
                }),
                Mockery::on(static function ($value) use ($schemaContent): bool {
                    return $value == $schemaContent;
                })
            )
            ->once()
            ->andReturn(Validator::ERROR_NONE);

        $request->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);

        $stream->shouldReceive('getContents')
            ->withNoArgs()
            ->once()
            ->andReturn(json_encode($input));

        $this->assertEquals(
            (object) $input,
            $this->subject->validate($request)
        );
    }
}
