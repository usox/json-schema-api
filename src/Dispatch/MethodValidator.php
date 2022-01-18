<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator;
use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;

/**
 * Validates input and output against the json schema to ensure valid requests/responses
 */
final class MethodValidator implements MethodValidatorInterface
{
    public function __construct(
        private Validator $schemaValidator,
        private ErrorFormatter $errorFormatter
    ) {
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
        if ($validationResult->isValid() === false) {
            throw new RequestMalformedException(
                'Bad Request',
                StatusCode::BAD_REQUEST
            );
        }
    }

    /**
     * @param array<mixed> $output
     *
     * @throws ResponseMalformedException
     */
    public function validateOutput(
        stdClass $methodSchemaContent,
        array $output
    ): void {
        if (property_exists($methodSchemaContent->properties, 'response') === true) {
            $data = new stdClass();
            $data->data = Helper::toJSON($output);

            // Wrap the response schema
            $response = (object) [
                'type' => 'object',
                'properties' => (object) [
                    'data' => $methodSchemaContent->properties->response,
                ],
                'required' => ['data']
            ];

            // Validate the response against the response definition in method schema
            $validationResult = $this->schemaValidator->validate(
                $data,
                $response
            );

            $error = $validationResult->error();
            // Throw exception if the input does not validate against the basic request schema
            if ($error !== null) {
                throw new ResponseMalformedException(
                    'Internal Server Error',
                    StatusCode::INTERNAL_SERVER_ERROR,
                    null,
                    $this->errorFormatter->format($error)
                );
            }
        }
    }
}
