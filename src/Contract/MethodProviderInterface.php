<?php

namespace Usox\JsonSchemaApi\Contract;

/**
 * A method provider should perform a lookup an api method by its name.
 * If api versions are used, the version number gets appended to the method name.
 * e.g. someNamespace.someMethod, someNamespace.someMethod.1
 *
 * If the method name does not exist, lookup is expected to return null.
 */
interface MethodProviderInterface
{
    public function lookup(
        string $methodName
    ): ?ApiMethodInterface;
}