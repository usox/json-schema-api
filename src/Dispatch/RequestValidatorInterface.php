<?php

namespace Usox\JsonSchemaApi\Dispatch;

use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Usox\JsonSchemaApi\Dispatch\Exception\JsonInvalidException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotFoundException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotLoadableException;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;

interface RequestValidatorInterface
{
    /**
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws SchemaNotLoadableException
     * @throws JsonInvalidException
     * @throws RequestMalformedException
     */
    public function validate(ServerRequestInterface $request): stdClass;
}
