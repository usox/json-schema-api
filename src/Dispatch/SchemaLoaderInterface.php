<?php

namespace Usox\JsonSchemaApi\Dispatch;

use Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotFoundException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotLoadableException;
use stdClass;

interface SchemaLoaderInterface
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
    ): stdClass;
}
