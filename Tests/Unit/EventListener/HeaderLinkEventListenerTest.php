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
    private HeaderLinkEventListener $listener;

    protected function setUp(): void
    {
        $this->listener = new HeaderLinkEventListener();
    }

    private function createSiteSettings(array $settingsTree): SiteSettings
    {
        return SiteSettings::createFromSettingsTree($settingsTree);
    }

    #[Test]
    public function addsHeaderLinkWhenNoApiKeyConfigured(): void
    {
        $siteSettings = $this->createSiteSettings(['llmsTxt' => ['apiKey' => '']]);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        $controller = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controller->content = '<html><head><title>Test</title></head><body></body></html>';

        $event = new AfterCacheableContentIsGeneratedEvent($request, $controller, 'abc123', false);

        ($this->listener)($event);

        self::assertStringContainsString('<link rel="alternate" type="text/plain" href="/llms.txt"', $controller->content);
    }

    #[Test]
    public function doesNotAddHeaderLinkWhenApiKeyConfigured(): void
    {
        $siteSettings = $this->createSiteSettings(['llmsTxt' => ['apiKey' => 'secret-key']]);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        $controller = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controller->content = '<html><head><title>Test</title></head><body></body></html>';

        $event = new AfterCacheableContentIsGeneratedEvent($request, $controller, 'abc123', false);

        ($this->listener)($event);

        self::assertStringNotContainsString('llms.txt', $controller->content);
    }

    #[Test]
    public function doesNothingWhenNoSiteAvailable(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn(null);

        $controller = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $originalContent = '<html><head><title>Test</title></head><body></body></html>';
        $controller->content = $originalContent;

        $event = new AfterCacheableContentIsGeneratedEvent($request, $controller, 'abc123', false);

        ($this->listener)($event);

        self::assertSame($originalContent, $controller->content);
    }

    #[Test]
    public function doesNothingWhenNoHeadTagPresent(): void
    {
        $siteSettings = $this->createSiteSettings(['llmsTxt' => ['apiKey' => '']]);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        $controller = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $originalContent = '<html><body>No head tag</body></html>';
        $controller->content = $originalContent;

        $event = new AfterCacheableContentIsGeneratedEvent($request, $controller, 'abc123', false);

        ($this->listener)($event);

        self::assertSame($originalContent, $controller->content);
    }

    #[Test]
    public function insertsLinkBeforeClosingHeadTag(): void
    {
        $siteSettings = $this->createSiteSettings(['llmsTxt' => ['apiKey' => '']]);

        $site = $this->createMock(Site::class);
        $site->method('getSettings')->willReturn($siteSettings);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);

        $controller = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controller->content = '<html><head><title>Test</title></head><body></body></html>';

        $event = new AfterCacheableContentIsGeneratedEvent($request, $controller, 'abc123', false);

        ($this->listener)($event);

        // The link should appear before </head>
        $linkPosition = strpos($controller->content, '<link rel="alternate"');
        $headClosePosition = strpos($controller->content, '</head>');

        self::assertNotFalse($linkPosition);
        self::assertNotFalse($headClosePosition);
        self::assertLessThan($headClosePosition, $linkPosition);
    }
}
