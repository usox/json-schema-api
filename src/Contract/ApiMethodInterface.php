<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Contract;

use stdClass;

interface ApiMethodInterface
{
    public function handle(stdClass $parameter): array;

    public function getSchemaFile(): string;
}
