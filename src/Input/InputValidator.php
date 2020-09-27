<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Input;

use Psr\Http\Message\RequestInterface;
use Usox\JsonSchemaApi\Exception\JsonInvalidException;
use Usox\JsonSchemaApi\Exception\RequestValidationException;
use JsonSchema\Validator;
use stdClass;
use Teapot\StatusCode;

final class InputValidator implements InputValidatorInterface
{
    private Validator $schemaValidator;

    public function __construct(
        Validator $schemaValidator
    ) {
        $this->schemaValidator = $schemaValidator;
    }

    /**
     * @throws JsonInvalidException
     * @throws RequestValidationException
     */
    public function validate(RequestInterface $request): stdClass 
    {
        // Decode the input and load the schema
        $decodedInput = json_decode($request->getBody()->getContents());

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonInvalidException(
                sprintf('Input is no valid json (%s)', json_last_error_msg()),
                StatusCode::BAD_REQUEST
            );
        }

        $schemaContent = json_decode(
            file_get_contents(__DIR__ . '/../../dist/request.json')
        );

        // First, validate the input against the basic request schema
        $validationResult = $this->schemaValidator->validate(
            $decodedInput,
            $schemaContent
        );

        // Throw exception if the input does not validate against the basic request schema
        if ($validationResult !== Validator::ERROR_NONE) {
            throw new RequestValidationException(
                'Request is invalid',
                StatusCode::BAD_REQUEST
            );
        }
        
        return $decodedInput;
    }
}