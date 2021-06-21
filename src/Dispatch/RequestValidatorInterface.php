<?php

namespace Usox\JsonSchemaApi\Dispatch;

use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Usox\JsonSchemaApi\Dispatch\Exception\JsonInvalidException;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;

interface RequestValidatorInterface
{
    /**
     * @throws Exception\SchemaInvalidException
     * @throws Exception\SchemaNotFoundException
     * @throws Exception\SchemaNotLoadableException
     * @throws JsonInvalidException
     * @throws RequestMalformedException
     */
    public function validate(ServerRequestInterface $request): stdClass;
}
