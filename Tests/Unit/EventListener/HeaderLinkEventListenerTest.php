<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RTfirst\LlmsTxt\EventListener\HeaderLinkEventListener;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

final class HeaderLinkEventListenerTest extends TestCase
{
    private HeaderLinkEventListener $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new HeaderLinkEventListener();
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

        $controller = $this->createMock(TypoScriptFrontendController::class);
        $controller->content = '<html><head><title>Test</title></head><body></body></html>';

        $event = new AfterCacheableContentIsGeneratedEvent(
            $request,
            $controller,
            'test-cache-id',
            true,
        );

        ($this->subject)($event);

        self::assertStringContainsString(
            '<link rel="alternate" type="text/plain" href="/llms.txt" title="LLM Content Guide">',
            $controller->content,
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

        $controller = $this->createMock(TypoScriptFrontendController::class);
        $controller->content = '<html><head><title>Test</title></head><body></body></html>';

        $event = new AfterCacheableContentIsGeneratedEvent(
            $request,
            $controller,
            'test-cache-id',
            true,
        );

        ($this->subject)($event);

        self::assertStringNotContainsString(
            '<link rel="alternate"',
            $controller->content,
        );
    }

    #[Test]
    public function returnsEarlyWhenNoSiteAvailable(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn(null);

        $controller = $this->createMock(TypoScriptFrontendController::class);
        $originalContent = '<html><head><title>Test</title></head><body></body></html>';
        $controller->content = $originalContent;

        $event = new AfterCacheableContentIsGeneratedEvent(
            $request,
            $controller,
            'test-cache-id',
            true,
        );

        ($this->subject)($event);

        self::assertSame($originalContent, $controller->content);
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

        $controller = $this->createMock(TypoScriptFrontendController::class);
        $controller->content = '<html><head><title>Test</title></head><body></body></html>';

        $event = new AfterCacheableContentIsGeneratedEvent(
            $request,
            $controller,
            'test-cache-id',
            true,
        );

        ($this->subject)($event);

        self::assertMatchesRegularExpression(
            '/<link rel="alternate"[^>]+>\n<\/head>/',
            $controller->content,
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

        $controller = $this->createMock(TypoScriptFrontendController::class);
        $originalContent = '<html><body>No head tag</body></html>';
        $controller->content = $originalContent;

        $event = new AfterCacheableContentIsGeneratedEvent(
            $request,
            $controller,
            'test-cache-id',
            true,
        );

        ($this->subject)($event);

        self::assertSame($originalContent, $controller->content);
    }
}
