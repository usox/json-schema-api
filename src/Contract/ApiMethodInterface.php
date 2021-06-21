<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Contract;

use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Usox\JsonSchemaApi\Exception\ApiMethodException;

interface ApiMethodInterface
{
    /**
     * This method contains the business logic of the api method
     *
     * @param stdClass $parameter The method parameter as described in the schema
     *
     * @return array<mixed, mixed> The api method response
     *
     * @throws ApiMethodException
     */
    public function handle(
        ServerRequestInterface $request,
        stdClass $parameter
    ): array;

    /**
     * Return the absolute path to the corresponding json schema
     */
    public function getSchemaFile(): string;
}
