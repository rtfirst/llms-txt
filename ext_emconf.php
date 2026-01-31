<?php

declare(strict_types=1);

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'LLMs.txt Generator',
    'description' => 'Generates llms.txt files for AI/LLM crawlers with website content in Markdown format',
    'category' => 'fe',
    'author' => 'Roland Tfirst',
    'author_email' => 'roland@tfirst.de',
    'state' => 'stable',
    'version' => '1.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
