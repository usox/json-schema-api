<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Exception;

use PHPUnit\Framework\TestCase;

class ResponseMalformedExceptionTest extends TestCase
{
    /** @var array<string, string> */
    private array $context = ['some' => 'context'];

    private ResponseMalformedException $subject;

    protected function setUp(): void
    {
        $this->subject = new ResponseMalformedException(
            '',
            0,
            null,
            $this->context,
        );
    }

    public function testGetContextReturnsData(): void
    {
        static::assertSame(
            $this->context,
            $this->subject->getContext(),
        );
    }
}
