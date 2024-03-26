<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Function_\AddFunctionVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
//        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
     ->withPhpSets(
    )
        ->withPreparedSets(
//            codeQuality: true

    )
    ->withRules([
//        AddVoidReturnTypeWhereNoReturnRector::class,
//        TypedPropertyFromStrictConstructorRector::class,
//        AddFunctionVoidReturnTypeWhereNoReturnRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
    ]);
