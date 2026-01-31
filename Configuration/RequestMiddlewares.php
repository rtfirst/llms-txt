<?php

declare(strict_types=1);

return [
    'frontend' => [
        'rtfirst/llms-txt/content-format' => [
            'target' => \RTfirst\LlmsTxt\Middleware\ContentFormatMiddleware::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers',
            ],
        ],
    ],
];
