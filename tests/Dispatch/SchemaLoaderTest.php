<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use org\bovigo\vfs\vfsStream;
use Teapot\StatusCode\Http;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotFoundException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotLoadableException;

class SchemaLoaderTest extends MockeryTestCase
{
    private SchemaLoader $subject;

    public function setUp(): void
    {
        $this->subject = new SchemaLoader();
    }

    public function testLoadThrowsExceptionIfSchemaWasNotFound(): void
    {
        $root = vfsStream::setup();
        $path = $root->url() . '/some-file';

        $this->expectException(SchemaNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf('Schema file `%s` not found', $path)
        );
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

        $this->subject->load($path);
    }

    public function testLoadThrowsExceptionIfSchemaDoesNotContainValidJson(): void
    {
        $root = vfsStream::setup();
        $content = 'some-content' . PHP_EOL . 'other-content';
        $path = $root->url() . '/some-file';

        file_put_contents($path, $content);

        $this->expectException(SchemaInvalidException::class);
        $this->expectExceptionMessage(
            'Schema does not contain valid json (Syntax error)'
        );
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

        $this->subject->load($path);
    }

    public function testLoadThrowsExceptionIfSchemaIsNotLoadable(): void
    {
        $root = vfsStream::setup();
        $path = $root->url();

        $this->expectException(SchemaNotLoadableException::class);
        $this->expectExceptionMessage(
            sprintf('Schema file `%s` not loadable', $path),
        );
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);


        $this->subject->load($path);
    }

    public function testLoadReturnsData(): void
    {
        $root = vfsStream::setup();
        $content = ['some' => 'content'];
        $path = $root->url() . '/some-file';

        file_put_contents($path, json_encode($content));

        static::assertSame(
            $content,
            (array) $this->subject->load($path)
        );
    }
}
