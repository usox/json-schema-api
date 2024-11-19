<?php

namespace Usox\JsonSchemaApi\Contract;

/**
 * Lookup a method name and return the api method handler (e.g. by using a dict<methodName, methodHandler>)
 *
 * If the method name does not exist, lookup is expected to return null.
 */
interface MethodProviderInterface
{
    /**
     * @return null|ApiMethodInterface<object, array<mixed>>
     */
    public function lookup(
        string $methodName,
    ): ?ApiMethodInterface;
}
