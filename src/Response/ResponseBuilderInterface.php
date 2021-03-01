<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Response;

use Ramsey\Uuid\UuidInterface;
use Throwable;

interface ResponseBuilderInterface
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildErrorResponse(
        Throwable $e,
        UuidInterface $uuid
    ): array;

    /**
     * @param array<mixed, mixed> $data
     * 
     * @return array<string, array<mixed, mixed>>
     */
    public function buildResponse(array $data): array;
}