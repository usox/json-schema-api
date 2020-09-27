<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Input;

use JsonSchema\Validator;
use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Exception\RequestValidationException;
use Usox\JsonSchemaApi\Exception\SchemaInvalidException;
use Usox\JsonSchemaApi\Exception\SchemaNotFoundException;

final class MethodValidator implements MethodValidatorInterface
{
    private Validator $schemaValidator;

    public function __construct(
        Validator $schemaValidator
    ) {
        $this->schemaValidator = $schemaValidator;
    }

    /**
     * @throws RequestValidationException
     * @throws SchemaNotFoundException
     * @throws SchemaInvalidException
     */
    public function validate(
        ApiMethodInterface $handler,
        stdClass $input
    ): void {
        $schemaFile = $handler->getSchemaFile();
        
        if (file_exists($schemaFile) === false) {
            throw new SchemaNotFoundException(
                sprintf('Schema file `%s` not found', $schemaFile),
                StatusCode::INTERNAL_SERVER_ERROR
            );
        }
        
        // Load the methods schema
        $methodSchemaContent = json_decode(
            file_get_contents($schemaFile)
        );

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SchemaInvalidException(
                sprintf('Schema does not contain valid json (%s)', json_last_error_msg()),
                StatusCode::INTERNAL_SERVER_ERROR
            );
        }

        // Validate the input parameter against the parameter definition in method schema
        $validationResult = $this->schemaValidator->validate(
            $input->parameter,
            $methodSchemaContent->properties->parameter
        );

        // Throw exception if the input does not validate against the basic request schema
        if ($validationResult !== Validator::ERROR_NONE) {
            throw new RequestValidationException(
                'Bad Request',
                StatusCode::BAD_REQUEST
            );
        }
    }
}