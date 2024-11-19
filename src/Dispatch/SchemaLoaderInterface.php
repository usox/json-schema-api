<?php

namespace Usox\JsonSchemaApi\Dispatch;

use stdClass;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaInvalidException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotFoundException;
use Usox\JsonSchemaApi\Dispatch\Exception\SchemaNotLoadableException;

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
        string $schemaFilePath,
    ): stdClass;
}
