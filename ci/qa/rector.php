<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Rector\Symfony\Symfony28\Rector\MethodCall\GetToConstructorInjectionRector;

return RectorConfig::configure()
    ->withPaths([
         __DIR__ . '/../../bin',
         __DIR__ . '/../../ci',
         __DIR__ . '/../../config',
         __DIR__ . '/../../public',
         __DIR__ . '/../../src',
         __DIR__ . '/../../tests',
         __DIR__ . '/../../templates',
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withPhpSets()
    ->withAttributesSets(all: true)
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withSkip([
        __DIR__ . '/../../config/bundles.php',
        __DIR__ . '/../../config/reference.php',
        NewMethodCallWithoutParenthesesRector::class,
        // This codebase relies on container-aware controllers resolving
        // dependencies via $this->get() and a service locator, not constructor
        // autowiring. This rule assumes autowiring and breaks the DI container.
        GetToConstructorInjectionRector::class,
        // Doctrine entities keep their mapped properties declared explicitly.
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity',
        ],
    ]);
