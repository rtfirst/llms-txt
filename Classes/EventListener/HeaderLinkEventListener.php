<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\EventListener;

use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

/**
 * Adds <link rel="alternate"> to llms.txt in HTML header.
 * Only active when no API key is configured (public access).
 */
final class HeaderLinkEventListener
{
    private const HEADER_LINK = '<link rel="alternate" type="text/plain" href="/llms.txt" title="LLM Content Guide">';

    public function __invoke(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $request = $event->getRequest();
        $site = $request->getAttribute('site');

        if (!$site instanceof SiteInterface) {
            return;
        }

        // Only add link when no API key is configured
        $apiKey = $site->getSettings()->get('llmsTxt.apiKey', '');
        if ($apiKey !== '') {
            return;
        }

        $controller = $event->getController();
        $content = $controller->content;

        // Insert link before </head>
        if (str_contains($content, '</head>')) {
            $controller->content = str_replace(
                '</head>',
                self::HEADER_LINK . "\n</head>",
                $content,
            );
        }
    }
}
