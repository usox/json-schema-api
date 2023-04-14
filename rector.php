<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->importNames();

    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ]);

    $rectorConfig->import(resource: SetList::CODE_QUALITY);
    $rectorConfig->import(SetList::DEAD_CODE);
    $rectorConfig->import(SetList::PHP_81);

    $services = $rectorConfig->services();
    $services->set(ClassPropertyAssignToConstructorPromotionRector::class);
    $services->set(RemoveUnusedVariableInCatchRector::class);

    $rectorConfig->skip([
        FlipTypeControlToUseExclusiveTypeRector::class,
    ]);
};
