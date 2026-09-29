<?php

declare(strict_types=1);

\defined('TYPO3') || die();

// Intro of the llms.txt of a language (Sites module > site > Languages).
// The site setting llmsTxt.intro is the fallback for the default language only.
$GLOBALS['SiteConfiguration']['site_language']['columns']['llmsTxtIntro'] = [
    'label' => 'LLL:EXT:rt_llms_txt/Resources/Private/Language/locallang.xlf:site_language.llmsTxtIntro',
    'description' => 'LLL:EXT:rt_llms_txt/Resources/Private/Language/locallang.xlf:site_language.llmsTxtIntro.description',
    'config' => [
        'type' => 'text',
        'rows' => 3,
        'cols' => 50,
    ],
];

$GLOBALS['SiteConfiguration']['site_language']['types']['1']['showitem'] .= ', llmsTxtIntro';
