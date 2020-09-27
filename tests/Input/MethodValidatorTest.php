<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Input;

use JsonSchema\Validator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Exception\RequestValidationException;
use Usox\JsonSchemaApi\Exception\SchemaInvalidException;
use Usox\JsonSchemaApi\Exception\SchemaNotFoundException;

class MethodValidatorTest extends MockeryTestCase
{
    /** @var Validator|MockInterface|null */
    private MockInterface $schemaValidator;
    
    /** @var MethodValidator|null */
    private MethodValidator $subject;
    
    public function setUp(): void
    {
        $this->schemaValidator = Mockery::mock(Validator::class);
        
        $this->subject = new MethodValidator(
            $this->schemaValidator
        );
    }
    
    public function testValidateThrowsExceptionIfSchemaWasNotFound(): void
    {
        $handler = Mockery::mock(ApiMethodInterface::class);
        $root = vfsStream::setup();
        $path = $root->url() . '/some-file';

        $this->expectException(SchemaNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf('Schema file `%s` not found', $path)
        );
        $this->expectExceptionCode(StatusCode::INTERNAL_SERVER_ERROR);

        $handler->shouldReceive('getSchemaFile')
            ->withNoArgs()
            ->once()
            ->andReturn($path);
        
        $this->subject->validate($handler, new stdClass());
    }

    public function testValidateThrowsExceptionIfSchemaDoesNotContainValidJson(): void
    {
        $handler = Mockery::mock(ApiMethodInterface::class);
        $root = vfsStream::setup();
        $content = 'some-content' . PHP_EOL . 'other-content';
        $path = $root->url() . '/some-file';
        
        file_put_contents($path, $content);

        $this->expectException(SchemaInvalidException::class);
        $this->expectExceptionMessage(
            'Schema does not contain valid json (Syntax error)'
        );
        $this->expectExceptionCode(StatusCode::INTERNAL_SERVER_ERROR);

        $handler->shouldReceive('getSchemaFile')
            ->withNoArgs()
            ->once()
            ->andReturn($path);

        $this->subject->validate($handler, new stdClass());
    }
    
    public function testValidateThrowsExceptionIfInputDoesNotValidate(): void
    {
        $handler = Mockery::mock(ApiMethodInterface::class);
        $root = vfsStream::setup();
        $path = $root->url() . '/some-file';
        $parameter = ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $input = (object) ['parameter' => $parameter];
        $content = ['properties' => ['parameter' => $schemaParameter]];
        $validationResult = 666;

        file_put_contents($path, json_encode($content));

        $this->expectException(RequestValidationException::class);
        $this->expectExceptionMessage('Bad Request');
        $this->expectExceptionCode(StatusCode::BAD_REQUEST);

        $handler->shouldReceive('getSchemaFile')
            ->withNoArgs()
            ->once()
            ->andReturn($path);
        
        $this->schemaValidator->shouldReceive('validate')
            ->with(
                $parameter,
                Mockery::on(static function($value) use ($schemaParameter): bool {
                    return (array) $value === $schemaParameter;
                })
            )
            ->once()
            ->andReturn($validationResult);

        $this->subject->validate($handler, $input);
    }

    public function testValidateValidates(): void
    {
        $handler = Mockery::mock(ApiMethodInterface::class);
        $root = vfsStream::setup();
        $path = $root->url() . '/some-file';
        $parameter = ['test' => 'param'];
        $schemaParameter = ['schema' => 'param'];
        $input = (object) ['parameter' => $parameter];
        $content = ['properties' => ['parameter' => $schemaParameter]];

        file_put_contents($path, json_encode($content));

        $handler->shouldReceive('getSchemaFile')
            ->withNoArgs()
            ->once()
            ->andReturn($path);

        $this->schemaValidator->shouldReceive('validate')
            ->with(
                $parameter,
                Mockery::on(static function($value) use ($schemaParameter): bool {
                    return (array) $value === $schemaParameter;
                })
            )
            ->once()
            ->andReturn(Validator::ERROR_NONE);

        $this->subject->validate($handler, $input);
    }
}
