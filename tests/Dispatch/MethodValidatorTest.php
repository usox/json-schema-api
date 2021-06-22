<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;

class MethodValidatorTest extends MockeryTestCase
{
    /** @var Validator|MockInterface */
    private MockInterface $schemaValidator;

    private MockInterface $errorFormatter;

    private MethodValidator $subject;

    public function setUp(): void
    {
        $this->schemaValidator = Mockery::mock(Validator::class);
        $this->errorFormatter = Mockery::mock(ErrorFormatter::class);

        $this->subject = new MethodValidator(
            $this->schemaValidator,
            $this->errorFormatter
        );
    }

    public function testValidateInputThrowsExceptionIfInputDoesNotValidate(): void
    {
        $parameter = ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $input = (object) ['parameter' => $parameter];
        $content = (object) ['properties' => (object) ['parameter' => $schemaParameter]];

        $validationResult = Mockery::mock(ValidationResult::class);

        $this->expectException(RequestMalformedException::class);
        $this->expectExceptionMessage('Bad Request');
        $this->expectExceptionCode(StatusCode::BAD_REQUEST);

        $this->schemaValidator->shouldReceive('validate')
            ->with(
                $parameter,
                Mockery::on(static function ($value) use ($schemaParameter): bool {
                    return (array) $value === $schemaParameter;
                })
            )
            ->once()
            ->andReturn($validationResult);

        $validationResult->shouldReceive('isValid')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->subject->validateInput(
            $content,
            $input
        );
    }

    public function testValidateInputValidates(): void
    {
        $parameter = ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $input = (object) ['parameter' => $parameter];
        $content = (object) ['properties' => (object) ['parameter' => $schemaParameter]];

        $result = Mockery::mock(ValidationResult::class);

        $this->schemaValidator->shouldReceive('validate')
            ->with(
                $parameter,
                Mockery::on(static function ($value) use ($schemaParameter): bool {
                    return (array) $value === $schemaParameter;
                })
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('isValid')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->subject->validateInput(
            $content,
            $input
        );
    }

    public function testValidateOutputThrowsExceptionIfOutputDoesNotValidate(): void
    {
        $output = (object) ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $content = (object) ['properties' => (object) ['response' => $schemaParameter]];
        $error = ['some' => 'error'];

        $validationResult = Mockery::mock(ValidationResult::class);
        $validationError = Mockery::mock(ValidationError::class);

        $this->expectException(ResponseMalformedException::class);
        $this->expectExceptionMessage('Internal Server Error');
        $this->expectExceptionCode(StatusCode::INTERNAL_SERVER_ERROR);

        $this->schemaValidator->shouldReceive('validate')
            ->with(
                $output,
                Mockery::on(static function ($value) use ($schemaParameter): bool {
                    return (array) $value === $schemaParameter;
                })
            )
            ->once()
            ->andReturn($validationResult);

        $validationResult->shouldReceive('isValid')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $validationResult->shouldReceive('error')
            ->withNoArgs()
            ->once()
            ->andReturn($validationError);

        $this->errorFormatter->shouldReceive('format')
            ->with($validationError)
            ->once()
            ->andReturn($error);

        $this->subject->validateOutput(
            $content,
            $output
        );
    }

    public function testValidateOutputValidates(): void
    {
        $output = (object) ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $content = (object) ['properties' => (object) ['response' => $schemaParameter]];

        $validationResult = Mockery::mock(ValidationResult::class);

        $this->schemaValidator->shouldReceive('validate')
            ->with(
                $output,
                Mockery::on(static function ($value) use ($schemaParameter): bool {
                    return (array) $value === $schemaParameter;
                })
            )
            ->once()
            ->andReturn($validationResult);

        $validationResult->shouldReceive('isValid')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->subject->validateOutput(
            $content,
            $output
        );
    }
}
