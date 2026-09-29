<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use RTfirst\LlmsTxt\Service\PageTreeService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageTreeServiceTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Pages.csv');
    }

    #[Test]
    public function pagesUnderSpacerAreIncluded(): void
    {
        $service = $this->get(PageTreeService::class);
        \assert($service instanceof PageTreeService);

        $site = $this->createSite();
        $pages = $service->getPages($site, $site->getDefaultLanguage());

        $uids = array_keys($pages);
        sort($uids);

        // Spacer (3, 6) absent; their descendants (4, 5, 7) present.
        // Excluded page (8) absent.
        self::assertSame([1, 2, 4, 5, 7], $uids);
    }

    #[Test]
    public function spacerPageItselfIsNotIncluded(): void
    {
        $service = $this->get(PageTreeService::class);
        \assert($service instanceof PageTreeService);

        $site = $this->createSite();
        $pages = $service->getPages($site, $site->getDefaultLanguage());

        self::assertArrayNotHasKey(3, $pages, 'Spacer page must not appear in output');
        self::assertArrayNotHasKey(6, $pages, 'Nested spacer page must not appear in output');
    }

    #[Test]
    public function childrenOfSpacersGetTheParentOfTheSpacer(): void
    {
        $service = $this->get(PageTreeService::class);
        \assert($service instanceof PageTreeService);

        $site = $this->createSite();
        $pages = $service->getPages($site, $site->getDefaultLanguage());

        $parents = array_map(static fn(array $page): mixed => $page['_LLMSTXT_PARENT'] ?? null, $pages);
        ksort($parents);

        // Spacers (3, 6) are skipped: their children (4, 7) belong to the
        // parent of the spacer (1). The grandchild (5) keeps its real parent.
        self::assertSame([1 => 0, 2 => 1, 4 => 1, 5 => 4, 7 => 1], $parents);
    }

    #[Test]
    public function explicitlyExcludedPageIsRespected(): void
    {
        $service = $this->get(PageTreeService::class);
        \assert($service instanceof PageTreeService);

        $site = $this->createSite();
        $pages = $service->getPages($site, $site->getDefaultLanguage(), [2]);

        self::assertArrayNotHasKey(2, $pages);
        self::assertArrayHasKey(4, $pages, 'Children of spacer remain when an unrelated page is excluded');
    }

    #[Test]
    public function untranslatedRootPageIsMissingInStrictLanguage(): void
    {
        $service = $this->get(PageTreeService::class);
        \assert($service instanceof PageTreeService);

        $site = $this->createSite();
        $pages = $service->getPages($site, $site->getLanguageById(1));

        $uids = array_keys($pages);
        sort($uids);

        // Root (1) has no translation, like the other untranslated pages (5, 7).
        // Its translated subpages (2, 4) are still collected.
        self::assertSame([2, 4], $uids);
    }

    #[Test]
    public function translatedPagesKeepThePriorityOfTheOriginalPage(): void
    {
        $service = $this->get(PageTreeService::class);
        \assert($service instanceof PageTreeService);

        $site = $this->createSite();
        $pages = $service->getPages($site, $site->getLanguageById(1));

        self::assertSame('Normal child EN', $pages[2]['title']);
        self::assertSame(40, (int)$pages[2]['tx_llmstxt_priority']);
    }

    private function createSite(): Site
    {
        return new Site('test', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'Default',
                    'locale' => 'en_US.UTF-8',
                    'base' => '/',
                ],
                [
                    'languageId' => 1,
                    'title' => 'German',
                    'locale' => 'de_DE.UTF-8',
                    'base' => '/de/',
                    'fallbackType' => 'strict',
                ],
            ],
        ]);
    }
}
