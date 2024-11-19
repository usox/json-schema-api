<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Response;

use Ramsey\Uuid\UuidInterface;
use Throwable;

final class ResponseBuilder implements ResponseBuilderInterface
{
    /**
     * @return array{error: array{message: string, code: int, id: string}}
     */
    public function buildErrorResponse(
        Throwable $e,
        UuidInterface $uuid,
    ): array {
        return [
            'error' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'id' => $uuid->toString(),
            ],
        ];
    }

    /**
     * @param array<mixed, mixed> $data
     *
     * @return array{data: array<mixed, mixed>}
     */
    public function buildResponse(array $data): array
    {
        return [
            'data' => $data,
        ];
    }
}
