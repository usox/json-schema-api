<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Exception;

use Exception;
use Throwable;

abstract class InternalException extends Exception
{
    /** @var array<mixed, mixed> */
    private array $context;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array<mixed, mixed> $context
     */
    public function __construct(
        string $message,
        int $code,
        Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);

        $this->context = $context;
    }

    /**
     * @return array<mixed, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}