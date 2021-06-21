<?php

namespace Usox\JsonSchemaApi\Dispatch;

use stdClass;
use Usox\JsonSchemaApi\Exception\RequestMalformedException;
use Usox\JsonSchemaApi\Exception\ResponseMalformedException;

interface MethodValidatorInterface
{
    /**
     * @throws RequestMalformedException
     */
    public function validateInput(
        stdClass $methodSchemaContent,
        stdClass $input
    ): void;

    /**
     * @throws ResponseMalformedException
     */
    public function validateOutput(
        stdClass $methodSchemaContent,
        stdClass $output
    ): void;
}
