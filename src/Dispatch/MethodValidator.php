<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use JsonSchema\Validator;
use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;

final class MethodValidator implements MethodValidatorInterface
{
    private Validator $schemaValidator;

    public function __construct(
        Validator $schemaValidator
    ) {
        $this->schemaValidator = $schemaValidator;
    }

    /**
     * @throws RequestMalformedException
     */
    public function validateInput(
        stdClass $methodSchemaContent,
        stdClass $input
    ): void {
        // Validate the input parameter against the parameter definition in method schema
        $validationResult = $this->schemaValidator->validate(
            $input->parameter,
            $methodSchemaContent->properties->parameter
        );

        // Throw exception if the input does not validate against the basic request schema
        if ($validationResult !== Validator::ERROR_NONE) {
            throw new RequestMalformedException(
                'Bad Request',
                StatusCode::BAD_REQUEST
            );
        }
    }

    /**
     * @throws ResponseMalformedException
     */
    public function validateOutput(
        stdClass $methodSchemaContent,
        stdClass $output
    ): void {
        if (property_exists($methodSchemaContent->properties, 'response') === true) {
            // Validate the response against the response definition in method schema
            $validationResult = $this->schemaValidator->validate(
                $output,
                $methodSchemaContent->properties->response
            );

            // Throw exception if the input does not validate against the basic request schema
            if ($validationResult !== Validator::ERROR_NONE) {
                throw new ResponseMalformedException(
                    'Internal Server Error',
                    StatusCode::INTERNAL_SERVER_ERROR,
                    null,
                    $this->schemaValidator->getErrors()
                );
            }
        }
    }
}
