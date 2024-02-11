<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Exception;

use Mockery\Adapter\Phpunit\MockeryTestCase;

class ResponseMalformedExceptionTest extends MockeryTestCase
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
            $this->context
        );
    }

    public function testGetContextReturnsData(): void
    {
        static::assertSame(
            $this->context,
            $this->subject->getContext()
        );
    }
}
