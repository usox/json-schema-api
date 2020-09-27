<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Response;

use Ramsey\Uuid\UuidInterface;
use Throwable;

final class ResponseBuilder implements ResponseBuilderInterface
{
    public function buildErrorResponse(
        Throwable $e,
        UuidInterface $uuid
    ): array {
        return [
            'error' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'id' => $uuid->toString()
            ]
        ];
    }

    public function buildResponse(array $data): array
    {
        return [
            'data' => $data
        ];
    }
}
