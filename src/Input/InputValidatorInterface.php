<?php

namespace Usox\JsonSchemaApi\Input;

use Psr\Http\Message\RequestInterface;
use stdClass;
use Usox\JsonSchemaApi\Exception\JsonInvalidException;
use Usox\JsonSchemaApi\Exception\RequestValidationException;

interface InputValidatorInterface
{
    /**
     * @throws JsonInvalidException
     * @throws RequestValidationException
     */
    public function validate(RequestInterface $request): stdClass;
}