<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Teapot\StatusCode\Http;
use Usox\JsonSchemaApi\Dispatch\Exception\JsonInvalidException;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;

class RequestValidatorTest extends TestCase
{
    private MockObject&SchemaLoaderInterface $schemaLoader;

    private MockObject&Validator $validator;

    private RequestValidator $subject;

    protected function setUp(): void
    {
        $this->schemaLoader = $this->createMock(SchemaLoaderInterface::class);
        $this->validator = $this->createMock(Validator::class);

        $this->subject = new RequestValidator(
            $this->schemaLoader,
            $this->validator,
        );
    }

    public function testValidateThrowsExceptionIfInputIsInvalid(): void
    {
        $this->expectException(JsonInvalidException::class);
        $this->expectExceptionMessage('Input is no valid json (Syntax error)');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $stream = $this->createMock(StreamInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $input = 'some-input' . PHP_EOL . 'errors';

        $request->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $stream->expects(static::once())
            ->method('getContents')
            ->willReturn($input);

        $this->subject->validate($request);
    }

    public function testValidateThrowsExceptionIfInputDoesNotValidate(): void
    {
        $this->expectException(RequestMalformedException::class);
        $this->expectExceptionMessage('Request is invalid');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $stream = $this->createMock(StreamInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $validationResult = $this->createMock(ValidationResult::class);

        $input = ['some' => 'input'];
        $schemaContent = (object) ['some' => 'schema-content'];

        $this->schemaLoader->expects(static::once())
            ->method('load')
            ->willReturn($schemaContent);

        $this->validator->expects(static::once())
            ->method('validate')
            ->with(
                $this->callback(static fn ($value): bool => (array) $value === $input),
                $this->callback(static fn ($value): bool => $value == $schemaContent),
            )
            ->willReturn($validationResult);

        $validationResult->expects(static::once())
            ->method('isValid')
            ->willReturn(false);

        $request->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $stream->expects(static::once())
            ->method('getContents')
            ->willReturn(json_encode($input));

        $this->subject->validate($request);
    }

    public function testValidateReturnsValidatedInput(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $validationResult = $this->createMock(ValidationResult::class);

        $input = ['some' => 'input'];
        $schemaContent = (object) ['some' => 'schema-content'];

        $this->schemaLoader->expects(static::once())
            ->method('load')
            ->willReturn($schemaContent);

        $this->validator->expects(static::once())
            ->method('validate')
            ->with(
                $this->callback(static fn ($value): bool => (array) $value === $input),
                $this->callback(static fn ($value): bool => $value == $schemaContent),
            )
            ->willReturn($validationResult);

        $validationResult->expects(static::once())
            ->method('isValid')
            ->willReturn(true);

        $request->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $stream->expects(static::once())
            ->method('getContents')
            ->willReturn(json_encode($input));

        static::assertEquals(
            (object) $input,
            $this->subject->validate($request),
        );
    }
}
