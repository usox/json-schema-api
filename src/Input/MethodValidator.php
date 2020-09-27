<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Input;

use JsonSchema\Validator;
use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Exception\RequestValidationException;

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
     */
    public function validate(
        ApiMethodInterface $handler,
        stdClass $input
    ): void {
        // Load the methods schema
        $methodSchemaContent = json_decode(
            file_get_contents($handler->getSchemaFile())
        );

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