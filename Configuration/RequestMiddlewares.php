<?php

declare(strict_types=1);

return [
    'frontend' => [
        // URL suffix middleware must execute BEFORE site resolver
        // Using 'before' timetracker to get the highest position number possible
        'rtfirst/llms-txt/url-suffix' => [
            'target' => \RTfirst\LlmsTxt\Middleware\UrlSuffixMiddleware::class,
            'before' => [
                'typo3/cms-frontend/timetracker',
            ],
        ],
        // llms.txt middleware runs AFTER site resolver to have site context
        'rtfirst/llms-txt/llms-txt' => [
            'target' => \RTfirst\LlmsTxt\Middleware\LlmsTxtMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/page-resolver',
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
