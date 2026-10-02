<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use RTfirst\LlmsTxt\Service\LlmsTxtGeneratorService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LlmsTxtGeneratorServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'frontend',
    ];

    protected array $testExtensionsToLoad = [
        'rtfirst/llms-txt',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/RedirectPages.csv');
    }

    #[Test]
    public function linkAndShortcutPagesHaveNoMarkdownLink(): void
    {
        $lines = explode("\n", $this->generate());

        self::assertContains('  - [Services](https://example.com/services)', $lines, 'Shortcut');
        self::assertContains('  - [Partner](https://example.com/partner): Our partner company.', $lines, 'Link');
    }

    #[Test]
    public function contentPagesKeepTheirMarkdownLink(): void
    {
        $lines = explode("\n", $this->generate());

        self::assertContains('- [Root](https://example.com/): [Markdown](https://example.com/index.html.md)', $lines);
        self::assertContains('    - [Consulting](https://example.com/services/consulting): [Markdown](https://example.com/services/consulting.md)', $lines, 'Subpage of a shortcut');
        self::assertContains('  - [Shared content](https://example.com/shared-content): [Markdown](https://example.com/shared-content.md)', $lines, 'Mount point showing the mounted page');
        self::assertContains('  - [Own content](https://example.com/own-content): [Markdown](https://example.com/own-content.md)', $lines, 'Mount point showing its own content');
    }

    #[Test]
    public function markdownExampleIsNoLinkOrShortcut(): void
    {
        // The first page below the root page is the shortcut "Services"
        self::assertStringContainsString(
            '- **Example:** `https://example.com/services/consulting.md`',
            $this->generate(),
        );
    }

    private function generate(): string
    {
        $service = $this->get(LlmsTxtGeneratorService::class);
        \assert($service instanceof LlmsTxtGeneratorService);

        $site = new Site('test', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'English',
                    'locale' => 'en_US.UTF-8',
                    'base' => '/',
                ],
            ],
        ]);

        return $service->getContentForSite($site);
    }
}
