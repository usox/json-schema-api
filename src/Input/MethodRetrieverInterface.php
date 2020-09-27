<?php

namespace Usox\JsonSchemaApi\Input;

use stdClass;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;

interface MethodRetrieverInterface
{
    public function retrieve(stdClass $input): ApiMethodInterface;
}