<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\EventListener;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RTfirst\LlmsTxt\Service\LlmsTxtGeneratorService;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;

/**
 * Event listener that generates llms.txt files when caches are flushed.
 */
final readonly class CacheFlushEventListener
{
    public function __construct(
        private LlmsTxtGeneratorService $generatorService,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(CacheFlushEvent $event): void
    {
        // Generate llms.txt on full cache flush or when pages cache is cleared
        $groups = $event->getGroups();

        // Generate if all caches are flushed (empty groups = all) or pages cache is included
        if ($groups === [] || $event->hasGroup('pages') || $event->hasGroup('all')) {
            try {
                $this->generatorService->generate();
                $this->logger->log(LogLevel::INFO, 'llms.txt files generated successfully after cache flush');
            } catch (Exception $e) {
                $this->logger->log(
                    LogLevel::ERROR,
                    'Failed to generate llms.txt files: {message}',
                    [
                        'message' => $e->getMessage(),
                        'exception' => $e,
                    ],
                );
            }
        }
    }
}
