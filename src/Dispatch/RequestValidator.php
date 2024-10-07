<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Teapot\StatusCode\Http;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotFoundException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotLoadableException;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Usox\JsonSchemaApi\Dispatch\Exception\JsonInvalidException;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;

/**
 * Validates the request against the basic request schema (method name, parameter)
 */
final readonly class RequestValidator implements RequestValidatorInterface
{
    public function __construct(
        private SchemaLoaderInterface $schemaLoader,
        private Validator $schemaValidator,
    ) {
    }

    /**
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws SchemaNotLoadableException
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
                Http::BAD_REQUEST
            );
        }

        $fileContent = $this->schemaLoader->load(__DIR__ . '/../../dist/request.json');

        /** @var stdClass $decodedInput */
        // First, validate the input against the basic request schema
        $validationResult = $this->schemaValidator->validate(
            $decodedInput,
            $fileContent
        );

        // Throw exception if the input does not validate against the basic request schema
        if (!$validationResult->isValid()) {
            throw new RequestMalformedException(
                'Request is invalid',
                Http::BAD_REQUEST
            );
        }

        return $decodedInput;
    }
}
