<?php

namespace Usox\JsonSchemaApi\Input;

use stdClass;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;

interface MethodValidatorInterface
{
    public function validate(ApiMethodInterface $handler, stdClass $input): void;
}