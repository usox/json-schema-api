<?php

namespace Usox\JsonSchemaApi\Dispatch;

use stdClass;

interface SchemaLoaderInterface
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
    ): stdClass;
}
