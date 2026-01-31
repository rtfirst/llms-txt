<?php

declare(strict_types=1);

\defined('TYPO3') or die();

// Exclude 'format' and 'api_key' parameters from cHash calculation
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'format';
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'api_key';

// Register cache for LLM format output (clean/md)
// This cache stores rendered format output to reduce database load
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['llms_txt_format'] ??= [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
    'options' => [
        'defaultLifetime' => 86400, // 24 hours
    ],
    'groups' => ['pages'],
];

// Note: Header link is added via Site Set TypoScript (setup.typoscript)
// It includes a condition to hide the link when API key protection is enabled
