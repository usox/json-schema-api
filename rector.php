<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;

return RectorConfig::configure()
    ->withImportNames()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ])
    ->withPreparedSets(deadCode: true, codeQuality: true, codingStyle: true)
    ->withPhpSets(php81: true)
    ->withRules([
        ClassPropertyAssignToConstructorPromotionRector::class,
        RemoveUnusedVariableInCatchRector::class,
    ])
    ->withSkip([
        FlipTypeControlToUseExclusiveTypeRector::class,
    ]);
