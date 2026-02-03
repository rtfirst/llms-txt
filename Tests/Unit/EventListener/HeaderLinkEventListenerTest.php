<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RTfirst\LlmsTxt\EventListener\HeaderLinkEventListener;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

/**
 * Tests for HeaderLinkEventListener.
 *
 * Note: These tests use different approaches for TYPO3 13 and 14 due to
 * the removal of TypoScriptFrontendController in TYPO3 14.
 */
final class HeaderLinkEventListenerTest extends TestCase
{
    private HeaderLinkEventListener $subject;
    private bool $isTypo3v14;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new HeaderLinkEventListener();
        // TYPO3 14 has getContent()/setContent() on the event
        // @phpstan-ignore function.alreadyNarrowedType (runtime check for TYPO3 version compatibility)
        $this->isTypo3v14 = method_exists(AfterCacheableContentIsGeneratedEvent::class, 'getContent');
    }

    #[Test]
    public function linkIsAddedWhenNoApiKeyConfigured(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([
            'llmsTxt' => ['apiKey' => ''],
        ]);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        $event = $this->createEvent($request, '<html><head><title>Test</title></head><body></body></html>');

        ($this->subject)($event);

        self::assertStringContainsString(
            '<link rel="alternate" type="text/plain" href="/llms.txt" title="LLM Content Guide">',
            $this->getContentFromEvent($event),
        );
    }

    #[Test]
    public function linkIsNotAddedWhenApiKeyConfigured(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([
            'llmsTxt' => ['apiKey' => 'secret-key'],
        ]);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        $event = $this->createEvent($request, '<html><head><title>Test</title></head><body></body></html>');

        ($this->subject)($event);

        self::assertStringNotContainsString(
            '<link rel="alternate"',
            $this->getContentFromEvent($event),
        );
    }

    #[Test]
    public function returnsEarlyWhenNoSiteAvailable(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn(null);

        $originalContent = '<html><head><title>Test</title></head><body></body></html>';
        $event = $this->createEvent($request, $originalContent);

        ($this->subject)($event);

        self::assertSame($originalContent, $this->getContentFromEvent($event));
    }

    #[Test]
    public function linkIsInsertedBeforeClosingHeadTag(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([
            'llmsTxt' => ['apiKey' => ''],
        ]);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        $event = $this->createEvent($request, '<html><head><title>Test</title></head><body></body></html>');

        ($this->subject)($event);

        self::assertMatchesRegularExpression(
            '/<link rel="alternate"[^>]+>\n<\/head>/',
            $this->getContentFromEvent($event),
        );
    }

    #[Test]
    public function noChangeWhenNoHeadTagPresent(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([
            'llmsTxt' => ['apiKey' => ''],
        ]);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        $originalContent = '<html><body>No head tag</body></html>';
        $event = $this->createEvent($request, $originalContent);

        ($this->subject)($event);

        self::assertSame($originalContent, $this->getContentFromEvent($event));
    }

    /**
     * Creates an event instance compatible with both TYPO3 13 and 14.
     *
     * @phpstan-ignore missingType.iterableValue (TYPO3 version-specific constructor)
     */
    private function createEvent(ServerRequestInterface $request, string $content): AfterCacheableContentIsGeneratedEvent
    {
        // @phpstan-ignore deadCode.unreachable (runtime check - this branch runs in TYPO3 14)
        if ($this->isTypo3v14) {
            // TYPO3 14: Event has getContent()/setContent()
            // @phpstan-ignore argument.type (TYPO3 14 constructor signature)
            return new AfterCacheableContentIsGeneratedEvent(
                $request,
                $content,
                'test-cache-id',
                true,
            );
        }

        // TYPO3 13: Event uses TypoScriptFrontendController
        $controller = $this->createMock(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class);
        $controller->content = $content;

        return new AfterCacheableContentIsGeneratedEvent(
            $request,
            $controller,
            'test-cache-id',
            true,
        );
    }

    /**
     * Gets content from event compatible with both TYPO3 13 and 14.
     */
    private function getContentFromEvent(AfterCacheableContentIsGeneratedEvent $event): string
    {
        // @phpstan-ignore deadCode.unreachable (runtime check - this branch runs in TYPO3 14)
        if ($this->isTypo3v14) {
            // @phpstan-ignore method.notFound (method exists in TYPO3 14)
            return $event->getContent();
        }

        return $event->getController()->content;
    }
}
