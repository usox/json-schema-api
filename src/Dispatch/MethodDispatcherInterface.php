<?php

namespace Usox\JsonSchemaApi\Dispatch;

use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotFoundException;
use Usox\JsonSchemaApi\Exception\ApiMethodException;
use Usox\JsonSchemaApi\Exception\MethodNotFoundException;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;

interface MethodDispatcherInterface
{
    /**
     * @return array<mixed, mixed>
     *
     * @throws MethodNotFoundException
     * @throws ApiMethodException
     * @throws RequestMalformedException
     * @throws ResponseMalformedException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     */
    public function dispatch(
        ServerRequestInterface $request,
        stdClass $input,
    ): array;
}
