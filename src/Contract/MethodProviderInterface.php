<?php

namespace Usox\JsonSchemaApi\Contract;

interface MethodProviderInterface
{
    public function lookup(
        string $methodName
    ): ?ApiMethodInterface;
}