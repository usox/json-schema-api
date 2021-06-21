<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Dispatch;

use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException;

final class SchemaLoader implements SchemaLoaderInterface
{
    /**
     * Loads and returns the schema content
     *
     * @throws Exception\SchemaInvalidException
     * @throws Exception\SchemaNotFoundException
     * @throws Exception\SchemaNotLoadableException
     */
    public function load(
        string $schemaFilePath
    ): stdClass {
        if (file_exists($schemaFilePath) === false) {
            throw new Exception\SchemaNotFoundException(
                sprintf('Schema file `%s` not found', $schemaFilePath),
                StatusCode::INTERNAL_SERVER_ERROR
            );
        }

        $fileContent = @file_get_contents($schemaFilePath);

        if ($fileContent === false) {
            throw new Exception\SchemaNotLoadableException(
                sprintf('Schema file `%s` not loadable', $schemaFilePath),
                StatusCode::INTERNAL_SERVER_ERROR
            );
        }

        // Load the methods schema
        $methodSchemaContent = json_decode($fileContent);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SchemaInvalidException(
                sprintf('Schema does not contain valid json (%s)', json_last_error_msg()),
                StatusCode::INTERNAL_SERVER_ERROR
            );
        }

        return $methodSchemaContent;
    }
}
