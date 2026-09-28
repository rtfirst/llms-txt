<?php

$EM_CONF['rt_llms_txt'] = [
    'title' => 'LLMs.txt Generator',
    'description' => 'Generates llms.txt files for AI/LLM crawlers with website content in Markdown format, with optional API key protection.',
    'category' => 'fe',
    'author' => 'Roland Tfirst',
    'author_email' => 'roland@tfirst.de',
    'state' => 'stable',
    'version' => '1.0.13',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    // Classic mode (non-Composer) only. This section replaces the autoload section of composer.json,
    // so it also lists the extension classes. league/html-to-markdown is bundled in TER packages
    // (Build/Scripts/bundleClassicDependencies.php); TYPO3 v14 includes its vendor directory via
    // extra.typo3/cms.Package.providesPackages in composer.json, TYPO3 v13 needs this mapping.
    'autoload' => [
        'psr-4' => [
            'RTfirst\\LlmsTxt\\' => 'Classes/',
            'League\\HTMLToMarkdown\\' => 'Resources/Private/Php/ComposerVendor/league/html-to-markdown/src/',
        ],
    ],
];
