<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use JsonSchema\Validator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;

class MethodValidatorTest extends MockeryTestCase
{
    /** @var Validator|MockInterface */
    private MockInterface $schemaValidator;

    private MethodValidator $subject;

    public function setUp(): void
    {
        $this->schemaValidator = Mockery::mock(Validator::class);

        $this->subject = new MethodValidator(
            $this->schemaValidator
        );
    }

    public function testValidateInputThrowsExceptionIfInputDoesNotValidate(): void
    {
        $parameter = ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $input = (object) ['parameter' => $parameter];
        $content = (object) ['properties' => (object) ['parameter' => $schemaParameter]];
        $validationResult = 666;

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

        $this->schemaValidator->shouldReceive('validate')
            ->with(
                $parameter,
                Mockery::on(static function ($value) use ($schemaParameter): bool {
                    return (array) $value === $schemaParameter;
                })
            )
            ->once()
            ->andReturn(Validator::ERROR_NONE);

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
        $errors = ['some' => 'error'];
        $validationResult = 666;

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
        $this->schemaValidator->shouldReceive('getErrors')
            ->withNoArgs()
            ->once()
            ->andReturn($errors);

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

        $this->schemaValidator->shouldReceive('validate')
            ->with(
                $output,
                Mockery::on(static function ($value) use ($schemaParameter): bool {
                    return (array) $value === $schemaParameter;
                })
            )
            ->once()
            ->andReturn(Validator::ERROR_NONE);

        $this->subject->validateOutput(
            $content,
            $output
        );
    }
}
