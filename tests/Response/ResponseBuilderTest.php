<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Response;

use Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

class ResponseBuilderTest extends TestCase
{
    private ResponseBuilder $subject;

    protected function setUp(): void
    {
        $this->subject = new ResponseBuilder();
    }

    public function testBuildErrorResponseReturnsData(): void
    {
        $message = 'some-error';
        $code = 666;
        $uuidValue = 'some-uuid';
        $error = new Exception($message, $code);

        $uuid = $this->createMock(UuidInterface::class);

        $uuid->expects(static::once())
            ->method('toString')
            ->willReturn($uuidValue);

        $this->assertSame(
            [
                'error' => [
                    'message' => $message,
                    'code' => $code,
                    'id' => $uuidValue,
                ],
            ],
            $this->subject->buildErrorResponse($error, $uuid),
        );
    }

    public function testBuildResponseReturnsResponse(): void
    {
        $data = ['some-data'];

        $this->assertSame(
            ['data' => $data],
            $this->subject->buildResponse($data),
        );
    }
}
