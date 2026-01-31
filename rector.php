<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
    ])
    ->withPhpSets(php82: true)
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
    ])
    ->withSkip([
        // Skip removal of unused event parameters in TYPO3 event listeners
        // Event listeners must keep the event parameter for TYPO3's event dispatcher
        RemoveUnusedPublicMethodParameterRector::class => [
            __DIR__ . '/Classes/EventListener/*',
        ],
    ]);
