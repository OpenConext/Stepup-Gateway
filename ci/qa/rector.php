<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
         __DIR__ . '/../../ci',
         __DIR__ . '/../../config',
         __DIR__ . '/../../public',
         __DIR__ . '/../../src',
         __DIR__ . '/../../tests',
         __DIR__ . '/../../templates',
    ])
    // uncomment to reach your current PHP version
//     ->withPhpSets()
    ->withAttributesSets(all: true)
    ->withComposerBased(phpunit: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
