<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use JsonSchema\Validator;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Exception\ApiMethodException;
use Usox\JsonSchemaApi\Exception\MethodNotFoundException;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotFoundException;

final class MethodDispatcher implements MethodDispatcherInterface
{
    private SchemaLoaderInterface $schemaLoader;
    
    private MethodValidatorInterface $methodValidator;

    private MethodProviderInterface $methodProvider;

    public function __construct(
        SchemaLoaderInterface $schemaLoader,
        MethodValidatorInterface $methodValidator,
        MethodProviderInterface $methodProvider
    ) {
        $this->schemaLoader = $schemaLoader;
        $this->methodValidator = $methodValidator;
        $this->methodProvider = $methodProvider;
    }

    /**
     * @return array<mixed, mixed>
     * 
     * @throws MethodNotFoundException
     * @throws ApiMethodException
     * @throws RequestMalformedException
     * @throws ResponseMalformedException
     * @throws \Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException
     * @throws SchemaNotFoundException
     */
    public function dispatch(
        ServerRequestInterface $request,
        stdClass $input
    ): array {
        // Get the method and version from the request
        $methodName = $input->method;
        $version = $input->version;
        
        if ($version !== null) {
            $methodName = sprintf('%s.%d', $methodName, $version);
        }

        $handler = $this->methodProvider->lookup($methodName);
        if ($handler === null) {
            throw new MethodNotFoundException(
                'Method not found',
                StatusCode::BAD_REQUEST
            );
        }
        
        $schemaContent = $this->schemaLoader->load($handler->getSchemaFile());

        $this->methodValidator->validateInput($schemaContent, $input);

        $response = $handler->handle($request, $input->parameter);
        
        /** @var stdClass $decodedResponse */
        $decodedResponse = Validator::arrayToObjectRecursive($response);
        
        $this->methodValidator->validateOutput(
            $schemaContent,
            $decodedResponse
        );
        
        return $response;
    }
}