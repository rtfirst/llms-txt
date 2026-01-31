<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

\defined('TYPO3') or die();

$llmsTxtColumns = [
    'tx_llmstxt_description' => [
        'label' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tx_llmstxt_description',
        'description' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tx_llmstxt_description.description',
        'config' => [
            'type' => 'text',
            'cols' => 40,
            'rows' => 3,
            'max' => 500,
        ],
    ],
    'tx_llmstxt_summary' => [
        'label' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tx_llmstxt_summary',
        'description' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tx_llmstxt_summary.description',
        'config' => [
            'type' => 'text',
            'cols' => 40,
            'rows' => 5,
            'max' => 2000,
            'enableRichtext' => false,
        ],
    ],
    'tx_llmstxt_keywords' => [
        'label' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tx_llmstxt_keywords',
        'description' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tx_llmstxt_keywords.description',
        'config' => [
            'type' => 'input',
            'size' => 50,
            'max' => 255,
            'eval' => 'trim',
        ],
    ],
    'tx_llmstxt_exclude' => [
        'label' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tx_llmstxt_exclude',
        'description' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tx_llmstxt_exclude.description',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'items' => [
                [
                    'label' => '',
                ],
            ],
        ],
    ],
    'tx_llmstxt_priority' => [
        'label' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tx_llmstxt_priority',
        'description' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tx_llmstxt_priority.description',
        'config' => [
            'type' => 'number',
            'size' => 5,
            'range' => [
                'lower' => 0,
                'upper' => 100,
            ],
            'default' => 0,
            'slider' => [
                'step' => 10,
                'width' => 200,
            ],
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('pages', $llmsTxtColumns);

// Add LLM tab to all page types (appears at the end)
ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:pages.tab.llm,
        tx_llmstxt_exclude,
        tx_llmstxt_priority,
        tx_llmstxt_description,
        tx_llmstxt_summary,
        tx_llmstxt_keywords',
);
