<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Opis\JsonSchema\Helper;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotLoadableException;
use Usox\JsonSchemaApi\Exception\ApiMethodException;
use Usox\JsonSchemaApi\Exception\MethodNotFoundException;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotFoundException;

final class MethodDispatcher implements MethodDispatcherInterface
{
    public function __construct(
        private SchemaLoaderInterface $schemaLoader,
        private MethodValidatorInterface $methodValidator,
        private MethodProviderInterface $methodProvider
    ) {
    }

    /**
     * @return array<mixed, mixed>
     *
     * @throws MethodNotFoundException
     * @throws ApiMethodException
     * @throws RequestMalformedException
     * @throws ResponseMalformedException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws SchemaNotLoadableException
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
        if (!$handler instanceof ApiMethodInterface) {
            throw new MethodNotFoundException(
                'Method not found',
                StatusCode::BAD_REQUEST
            );
        }

        $schemaContent = $this->schemaLoader->load($handler->getSchemaFile());

        $this->methodValidator->validateInput($schemaContent, $input);

        $response = $handler->handle($request, $input->parameter);

        /** @var stdClass $decodedResponse */
        $decodedResponse = (object) Helper::toJSON($response);

        $this->methodValidator->validateOutput(
            $schemaContent,
            $decodedResponse
        );

        return $response;
    }
}
