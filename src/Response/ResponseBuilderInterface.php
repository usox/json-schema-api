<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Response;

use Ramsey\Uuid\UuidInterface;
use Throwable;

interface ResponseBuilderInterface
{
    public function buildErrorResponse(
        Throwable $e,
        UuidInterface $uuid
    ): array;

    public function buildResponse(array $data): array;
}