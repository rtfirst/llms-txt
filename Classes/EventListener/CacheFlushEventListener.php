<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Event listener that invalidates llms.txt cache when caches are flushed.
 */
final readonly class CacheFlushEventListener
{
    public function __construct(
        private FrontendInterface $cache,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(CacheFlushEvent $event): void
    {
        // Invalidate llms.txt cache on full cache flush or when pages cache is cleared
        $groups = $event->getGroups();

        // Flush if all caches are flushed (empty groups = all) or pages cache is included
        if ($groups === [] || $event->hasGroup('pages') || $event->hasGroup('all')) {
            $this->flushLlmsTxtCache();
        }
    }

    /**
     * Flush all llms.txt cache entries (index and per-page Markdown) for all sites.
     */
    private function flushLlmsTxtCache(): void
    {
        $this->cache->flush();

        $this->logger->log(LogLevel::INFO, 'llms.txt cache invalidated for all sites');
    }
}
