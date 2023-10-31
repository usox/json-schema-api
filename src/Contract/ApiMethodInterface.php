<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Contract;

use Psr\Http\Message\ServerRequestInterface;
use Usox\JsonSchemaApi\Exception\ApiMethodException;

/**
 * @template TParameter of object
 * @template TResult of array
 */
interface ApiMethodInterface
{
    /**
     * This method contains the business logic of the api method
     *
     * @param TParameter $parameter The method parameter as described in the schema
     *
     * @return TResult The api method response
     *
     * @throws ApiMethodException
     */
    public function handle(
        ServerRequestInterface $request,
        object $parameter
    ): array;

    /**
     * Return the absolute path to the corresponding json schema
     */
    public function getSchemaFile(): string;
}
