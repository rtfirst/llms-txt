<?php

declare(strict_types=1);

return [
    'frontend' => [
        // URL suffix middleware runs BEFORE routing to detect .md suffix
        'rtfirst/llms-txt/url-suffix' => [
            'target' => \RTfirst\LlmsTxt\Middleware\UrlSuffixMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'typo3/cms-frontend/site',
            ],
        ],
        // Content format middleware runs AFTER routing to transform content
        'rtfirst/llms-txt/content-format' => [
            'target' => \RTfirst\LlmsTxt\Middleware\ContentFormatMiddleware::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers',
            ],
        ],
    ],
];
