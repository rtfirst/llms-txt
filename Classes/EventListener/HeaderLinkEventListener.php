<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\EventListener;

use TYPO3\CMS\Core\Site\Entity\Site;
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

        if (!$site instanceof Site) {
            return;
        }

        // Only add link when no API key is configured
        $apiKey = $site->getSettings()->get('llmsTxt.apiKey', '');
        if ($apiKey !== '') {
            return;
        }

        // TYPO3 14: use getContent()/setContent(), TYPO3 13: use getController()->content
        // @phpstan-ignore function.impossibleType (runtime check for TYPO3 version compatibility)
        if (method_exists($event, 'getContent')) {
            // TYPO3 14+
            $content = $event->getContent();
            if (str_contains($content, '</head>')) {
                $event->setContent(str_replace(
                    '</head>',
                    self::HEADER_LINK . "\n</head>",
                    $content,
                ));
            }
        } else {
            // TYPO3 13
            $controller = $event->getController();
            $content = $controller->content;
            if (str_contains($content, '</head>')) {
                $controller->content = str_replace(
                    '</head>',
                    self::HEADER_LINK . "\n</head>",
                    $content,
                );
            }
        }
    }
}
