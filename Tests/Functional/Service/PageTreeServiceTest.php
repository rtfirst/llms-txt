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
    public function explicitlyExcludedPageIsRespected(): void
    {
        $service = $this->get(PageTreeService::class);
        \assert($service instanceof PageTreeService);

        $site = $this->createSite();
        $pages = $service->getPages($site, $site->getDefaultLanguage(), [2]);

        self::assertArrayNotHasKey(2, $pages);
        self::assertArrayHasKey(4, $pages, 'Children of spacer remain when an unrelated page is excluded');
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
            ],
        ]);
    }
}
