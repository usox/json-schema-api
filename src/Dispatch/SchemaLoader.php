<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use Teapot\StatusCode\Http;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotFoundException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotLoadableException;
use stdClass;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException;

/**
 * Lookup the schema and load its contents
 */
final class SchemaLoader implements SchemaLoaderInterface
{
    /**
     * Loads and returns the schema content
     *
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws SchemaNotLoadableException
     */
    public function load(
        string $schemaFilePath
    ): stdClass {
        if (!file_exists($schemaFilePath)) {
            throw new SchemaNotFoundException(
                sprintf('Schema file `%s` not found', $schemaFilePath),
                Http::INTERNAL_SERVER_ERROR
            );
        }

        $fileContent = @file_get_contents($schemaFilePath);

        if ($fileContent === false) {
            throw new SchemaNotLoadableException(
                sprintf('Schema file `%s` not loadable', $schemaFilePath),
                Http::INTERNAL_SERVER_ERROR
            );
        }

        // Load the methods schema
        $methodSchemaContent = json_decode($fileContent);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SchemaInvalidException(
                sprintf('Schema does not contain valid json (%s)', json_last_error_msg()),
                Http::INTERNAL_SERVER_ERROR
            );
        }

        /** @var stdClass $methodSchemaContent */
        return $methodSchemaContent;
    }
}
