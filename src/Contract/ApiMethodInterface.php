<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Contract;

use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Usox\JsonSchemaApi\Exception\ApiMethodException;

interface ApiMethodInterface
{
    /**
     * @throws ApiMethodException
     */
    public function handle(
        ServerRequestInterface $request,
        stdClass $parameter
    ): array;

    public function getSchemaFile(): string;
}
