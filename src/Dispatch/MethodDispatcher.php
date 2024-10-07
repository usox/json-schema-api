<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use Teapot\StatusCode\Http;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotFoundException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotLoadableException;
use Usox\JsonSchemaApi\Exception\ApiMethodException;
use Usox\JsonSchemaApi\Exception\MethodNotFoundException;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;

/**
 * Handles the life cycle of a method call
 * - load method handler
 * - validate input
 * - execute handler
 * - validate output
 */
final readonly class MethodDispatcher implements MethodDispatcherInterface
{
    public function __construct(
        private SchemaLoaderInterface $schemaLoader,
        private MethodValidatorInterface $methodValidator,
        private MethodProviderInterface $methodProvider,
        private ?LoggerInterface $logger
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
        // Get the method from the request and perform lookup
        $methodName = $input->method;

        $this->logger?->debug(
            'Api method call',
            [
                'method' => $methodName,
                'input' => $input->parameter
            ]
        );

        $handler = $this->methodProvider->lookup($methodName);
        if (!$handler instanceof ApiMethodInterface) {
            throw new MethodNotFoundException(
                'Method not found',
                Http::BAD_REQUEST
            );
        }

        $schemaContent = $this->schemaLoader->load($handler->getSchemaFile());

        $this->methodValidator->validateInput($schemaContent, $input);

        $this->logger?->info(
            'Api method call',
            [
                'method' => $methodName,
            ]
        );

        $response = $handler->handle($request, $input->parameter);

        $this->methodValidator->validateOutput(
            $schemaContent,
            $response
        );

        return $response;
    }
}
