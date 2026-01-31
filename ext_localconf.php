<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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

// Add TypoScript for headerData (link tag)
ExtensionManagementUtility::addTypoScriptSetup('
# LLMs.txt Header Link Tag
# Adds <link rel="alternate"> pointing to llms.txt for all pages

page.headerData.999 = TEXT
page.headerData.999.value = <link rel="alternate" type="text/plain" href="/llms.txt" title="LLM Content Guide">
');
