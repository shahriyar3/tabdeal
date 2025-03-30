<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/bundles/*/Test',
        __DIR__.'/app/bundles/*/Tests',
        __DIR__.'/plugins/*/Test',
        __DIR__.'/plugins/*/Tests',
    ])
    ->withCache(__DIR__.'/var/cache/rector-tests')
    ->withSets([
        PHPUnitSetList::PHPUNIT_60,
        PHPUnitSetList::PHPUNIT_90,
        PHPUnitSetList::PHPUNIT_100,
    ])
    ->withRules([
        \Rector\PHPUnit\PHPUnit80\Rector\MethodCall\SpecificAssertContainsRector::class,
        \Rector\PHPUnit\PHPUnit60\Rector\MethodCall\GetMockBuilderGetMockToCreateMockRector::class,
    ])
    ->withSkip([
        \Rector\PHPUnit\PHPUnit60\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRector::class, // Adds annotation where it does not belong to.
    ]);
