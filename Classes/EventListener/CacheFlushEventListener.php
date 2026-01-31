<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RTfirst\LlmsTxt\Service\LlmsTxtGeneratorService;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Event listener that invalidates llms.txt cache when caches are flushed.
 */
final readonly class CacheFlushEventListener
{
    private const CACHE_KEY_PREFIX = 'llmstxt_index_';

    public function __construct(
        private LlmsTxtGeneratorService $generatorService,
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
     * Flush all llms.txt cache entries for all sites.
     */
    private function flushLlmsTxtCache(): void
    {
        $siteIdentifiers = $this->generatorService->getAllSiteIdentifiers();

        foreach ($siteIdentifiers as $identifier) {
            $cacheKey = self::CACHE_KEY_PREFIX . $identifier;
            if ($this->cache->has($cacheKey)) {
                $this->cache->remove($cacheKey);
            }
        }

        $this->logger->log(LogLevel::INFO, 'llms.txt cache invalidated for all sites');
    }
}
