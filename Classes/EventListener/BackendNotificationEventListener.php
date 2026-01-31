<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\EventListener;

use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Event listener that shows a notification in the backend if robots.txt needs updating.
 */
final readonly class BackendNotificationEventListener
{
    private const ROBOTS_TXT_MARKER = 'llms.txt';

    public function __construct(
        private FlashMessageService $flashMessageService,
    ) {}

    public function __invoke(AfterBackendPageRenderEvent $event): void
    {
        // Only check once per session to avoid spamming
        $sessionKey = 'llms_txt_robots_checked';
        if (isset($GLOBALS['BE_USER']->uc[$sessionKey])) {
            return;
        }

        $robotsTxtPath = Environment::getPublicPath() . '/robots.txt';

        // Check if robots.txt exists and contains llms.txt reference
        if (file_exists($robotsTxtPath)) {
            $content = file_get_contents($robotsTxtPath);
            if ($content !== false && str_contains($content, self::ROBOTS_TXT_MARKER)) {
                // Already configured, mark as checked
                $GLOBALS['BE_USER']->uc[$sessionKey] = true;
                $GLOBALS['BE_USER']->writeUC();

                return;
            }
        }

        // Show notification
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            'The llms_txt extension recommends adding llms.txt references to your robots.txt file. '
                . 'See the extension documentation for details.',
            'LLMs.txt: robots.txt configuration',
            ContextualFeedbackSeverity::INFO,
            true,
        );

        $messageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->enqueue($message);

        // Mark as checked for this session
        $GLOBALS['BE_USER']->uc[$sessionKey] = true;
        $GLOBALS['BE_USER']->writeUC();
    }
}
