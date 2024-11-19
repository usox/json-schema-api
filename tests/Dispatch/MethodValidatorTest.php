<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;

class MethodValidatorTest extends TestCase
{
    private MockObject&Validator $schemaValidator;

    private MockObject&ErrorFormatter $errorFormatter;

    private MethodValidator $subject;

    protected function setUp(): void
    {
        $this->schemaValidator = $this->createMock(Validator::class);
        $this->errorFormatter = $this->createMock(ErrorFormatter::class);

        $this->subject = new MethodValidator(
            $this->schemaValidator,
            $this->errorFormatter,
        );
    }

    public function testValidateInputThrowsExceptionIfInputDoesNotValidate(): void
    {
        $parameter = ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $input = (object) ['parameter' => $parameter];
        $content = (object) ['properties' => (object) ['parameter' => $schemaParameter]];

        $validationResult = $this->createMock(ValidationResult::class);

        $this->expectException(RequestMalformedException::class);
        $this->expectExceptionMessage('Bad Request');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->schemaValidator->expects(static::once())
            ->method('validate')
            ->with(
                $parameter,
                $this->callback(static fn ($value): bool => json_encode($value) === json_encode($schemaParameter)),
            )
            ->willReturn($validationResult);

        $validationResult->expects(static::once())
            ->method('isValid')
            ->willReturn(false);

        $this->subject->validateInput(
            $content,
            $input,
        );
    }

    public function testValidateInputValidates(): void
    {
        $parameter = ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $input = (object) ['parameter' => $parameter];
        $content = (object) ['properties' => (object) ['parameter' => $schemaParameter]];

        $result = $this->createMock(ValidationResult::class);

        $this->schemaValidator->expects(static::once())
            ->method('validate')
            ->with(
                $parameter,
                $this->callback(static fn ($value): bool => (array) $value === $schemaParameter),
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('isValid')
            ->willReturn(true);

        $this->subject->validateInput(
            $content,
            $input,
        );
    }

    public function testValidateOutputThrowsExceptionIfOutputDoesNotValidate(): void
    {
        $output = ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $content = (object) ['properties' => (object) ['response' => $schemaParameter]];
        $error = ['some' => 'error'];

        $validationResult = $this->createMock(ValidationResult::class);
        $validationError = $this->createMock(ValidationError::class);

        $this->expectException(ResponseMalformedException::class);
        $this->expectExceptionMessage('Internal Server Error');
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

        $this->schemaValidator->expects(static::once())
            ->method('validate')
            ->with(
                $this->callback(static fn ($value): bool => json_encode($value) === json_encode(['data' => $output])),
                $this->callback(static function ($value) use ($schemaParameter): bool {
                    $schemaParameter = [
                        'type' => 'object',
                        'properties' => [
                            'data' => $schemaParameter,
                        ],
                        'required' => ['data'],

                    ];
                    return json_encode($value) === json_encode($schemaParameter);
                }),
            )
            ->willReturn($validationResult);

        $validationResult->expects(static::once())
            ->method('error')
            ->willReturn($validationError);

        $this->errorFormatter->expects(static::once())
            ->method('format')
            ->with($validationError)
            ->willReturn($error);

        $this->subject->validateOutput(
            $content,
            $output,
        );
    }

    public function testValidateOutputValidates(): void
    {
        $output = ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $content = (object) ['properties' => (object) ['response' => $schemaParameter]];

        $validationResult = $this->createMock(ValidationResult::class);

        $this->schemaValidator->expects(static::once())
            ->method('validate')
            ->with(
                $this->callback(static fn ($value): bool => json_encode($value) === json_encode(['data' => $output])),
                $this->callback(static function ($value) use ($schemaParameter): bool {
                    $schemaParameter = [
                        'type' => 'object',
                        'properties' => [
                            'data' => $schemaParameter,
                        ],
                        'required' => ['data'],

                    ];
                    return json_encode($value) === json_encode($schemaParameter);
                }),
            )
            ->willReturn($validationResult);

        $validationResult->expects(static::once())
            ->method('error')
            ->willReturn(null);

        $this->subject->validateOutput(
            $content,
            $output,
        );
    }
}
