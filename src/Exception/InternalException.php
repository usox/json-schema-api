<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Exception;

use Exception;
use Throwable;

abstract class InternalException extends Exception
{
    /**
     * @param Throwable|null $previous
     * @param array<mixed, mixed> $context
     */
    public function __construct(
        string $message,
        int $code,
        Throwable $previous = null,
        private readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<mixed, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
