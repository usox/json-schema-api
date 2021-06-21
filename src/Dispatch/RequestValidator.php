<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use JsonSchema\Validator;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Dispatch\Exception\JsonInvalidException;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;

final class RequestValidator implements RequestValidatorInterface
{
    private SchemaLoaderInterface $schemaLoader;

    private Validator $schemaValidator;

    public function __construct(
        SchemaLoaderInterface $schemaLoader,
        Validator $schemaValidator
    ) {
        $this->schemaLoader = $schemaLoader;
        $this->schemaValidator = $schemaValidator;
    }

    /**
     * @throws Exception\SchemaInvalidException
     * @throws Exception\SchemaNotFoundException
     * @throws Exception\SchemaNotLoadableException
     * @throws JsonInvalidException
     * @throws RequestMalformedException
     */
    public function validate(ServerRequestInterface $request): stdClass
    {
        // Decode the input and load the schema
        $decodedInput = json_decode($request->getBody()->getContents());

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonInvalidException(
                sprintf('Input is no valid json (%s)', json_last_error_msg()),
                StatusCode::BAD_REQUEST
            );
        }

        $fileContent = $this->schemaLoader->load(__DIR__ . '/../../dist/request.json');

        // First, validate the input against the basic request schema
        $validationResult = $this->schemaValidator->validate(
            $decodedInput,
            $fileContent
        );

        // Throw exception if the input does not validate against the basic request schema
        if ($validationResult !== Validator::ERROR_NONE) {
            throw new RequestMalformedException(
                'Request is invalid',
                StatusCode::BAD_REQUEST
            );
        }

        return $decodedInput;
    }
}
