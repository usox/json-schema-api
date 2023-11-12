<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Teapot\StatusCode\Http;
use Usox\JsonSchemaApi\Dispatch\Exception\JsonInvalidException;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;

class RequestValidatorTest extends MockeryTestCase
{
    private MockInterface&SchemaLoaderInterface $schemaLoader;

    private MockInterface&Validator $validator;

    private RequestValidator $subject;

    public function setUp(): void
    {
        $this->schemaLoader = Mockery::mock(SchemaLoaderInterface::class);
        $this->validator = Mockery::mock(Validator::class);

        $this->subject = new RequestValidator(
            $this->schemaLoader,
            $this->validator
        );
    }

    public function testValidateThrowsExceptionIfInputIsInvalid(): void
    {
        $this->expectException(JsonInvalidException::class);
        $this->expectExceptionMessage('Input is no valid json (Syntax error)');
        $this->expectExceptionCode(Http::BAD_REQUEST);

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
        $this->expectException(RequestMalformedException::class);
        $this->expectExceptionMessage('Request is invalid');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $stream = Mockery::mock(StreamInterface::class);
        $request = Mockery::mock(ServerRequestInterface::class);
        $validationResult = Mockery::mock(ValidationResult::class);

        $input = ['some' => 'input'];
        $schemaContent = (object) ['some' => 'schema-content'];

        $this->schemaLoader->shouldReceive('load')
            ->once()
            ->andReturn($schemaContent);

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
            ->andReturn($validationResult);

        $validationResult->shouldReceive('isValid')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

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
        $validationResult = Mockery::mock(ValidationResult::class);

        $input = ['some' => 'input'];
        $schemaContent = (object) ['some' => 'schema-content'];

        $this->schemaLoader->shouldReceive('load')
            ->once()
            ->andReturn($schemaContent);

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
            ->andReturn($validationResult);

        $validationResult->shouldReceive('isValid')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);

        $stream->shouldReceive('getContents')
            ->withNoArgs()
            ->once()
            ->andReturn(json_encode($input));

        static::assertEquals(
            (object) $input,
            $this->subject->validate($request)
        );
    }
}
