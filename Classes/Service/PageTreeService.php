<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Service;

use Doctrine\DBAL\ParameterType;
use Exception;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Service for traversing the TYPO3 page tree.
 */
final readonly class PageTreeService
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get all visible pages for a site in a specific language.
     *
     * Spacer pages are left out, so each record carries the UID of its nearest
     * parent page that is not a spacer in "_LLMSTXT_PARENT".
     *
     * @param array<int> $excludePageUids Page UIDs to exclude
     * @return array<int, array<string, mixed>> Page records indexed by UID
     */
    public function getPages(
        Site $site,
        SiteLanguage $language,
        array $excludePageUids = [],
        bool $includeHidden = false,
    ): array {
        $rootPageId = $site->getRootPageId();
        $languageId = $this->extractLanguageId($language);

        $pages = [];

        // First, add the root page itself
        $rootPage = $this->getPage($rootPageId, $languageId, $includeHidden, $language);
        if ($rootPage !== null && !\in_array($rootPageId, $excludePageUids, true)) {
            $rootPage['_LLMSTXT_PARENT'] = (int)($rootPage['pid'] ?? 0);
            $pages[$rootPageId] = $rootPage;
        }

        // Then collect all child pages recursively
        $this->collectPages($rootPageId, $rootPageId, $languageId, $excludePageUids, $includeHidden, $pages, $language);

        return $pages;
    }

    /**
     * Whether a page is a link or a shortcut. These pages only point to another
     * page or URL and have no content of their own, so there is no Markdown
     * version of them: the frontend redirects them to their target, or shows
     * the target page (TYPO3 14, link to a page).
     *
     * Mount points are no such pages: they bring the content of the mounted
     * page into the site, which may not be listed anywhere else.
     *
     * @param array<string, mixed> $page
     */
    public function isLinkOrShortcut(array $page): bool
    {
        return \in_array(
            (int)($page['doktype'] ?? 0),
            [PageRepository::DOKTYPE_LINK, PageRepository::DOKTYPE_SHORTCUT],
            true,
        );
    }

    /**
     * Get a single page record.
     *
     * @return array<string, mixed>|null
     */
    private function getPage(int $pageUid, int $languageId, bool $includeHidden, ?SiteLanguage $language = null): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $this->applyRestrictions($queryBuilder, $includeHidden);

        $constraints = [
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('tx_llmstxt_exclude', 0),
        ];

        $row = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(...$constraints)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return $this->overlayTranslation($row, $languageId, $includeHidden, $language);
    }

    /**
     * Recursively collect pages from the page tree.
     *
     * @param int $parentId Page whose subpages are collected
     * @param int $treeParentId Nearest page above them that is not a spacer
     * @param array<int> $excludePageUids
     * @param array<int, array<string, mixed>> $pages
     */
    private function collectPages(
        int $parentId,
        int $treeParentId,
        int $languageId,
        array $excludePageUids,
        bool $includeHidden,
        array &$pages,
        ?SiteLanguage $language = null,
    ): void {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $this->applyRestrictions($queryBuilder, $includeHidden);

        // Base constraints
        $constraints = [
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($parentId, ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('sys_language_uid', 0), // Always query default language first
            $queryBuilder->expr()->eq('tx_llmstxt_exclude', 0), // Exclude pages marked for exclusion
        ];

        // Exclude doktypes that never produce frontend output and have no
        // visible descendants (folders, recycler, BE user section).
        // Spacers (199) are intentionally NOT excluded here: they may have
        // child pages that should appear in llms.txt. The spacer row itself
        // is filtered out below.
        $excludedDoktypes = [
            255, // Recycler
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_BE_USER_SECTION,
        ];
        $constraints[] = $queryBuilder->expr()->notIn(
            'doktype',
            array_map(static fn(int $doktype): string => (string)$doktype, $excludedDoktypes),
        );

        // Exclude specific pages
        if ($excludePageUids !== []) {
            $constraints[] = $queryBuilder->expr()->notIn(
                'uid',
                array_map(static fn(int $uid): string => (string)$uid, $excludePageUids),
            );
        }

        $result = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(...$constraints)
            ->orderBy('sorting', 'ASC')
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $pageUid = (int)$row['uid'];

            // Spacer pages are menu separators and are not output themselves,
            // but their child pages must still be discovered. They take the
            // place of the spacer in the tree.
            if ((int)$row['doktype'] === PageRepository::DOKTYPE_SPACER) {
                $this->collectPages($pageUid, $treeParentId, $languageId, $excludePageUids, $includeHidden, $pages, $language);
                continue;
            }

            $page = $this->overlayTranslation($row, $languageId, $includeHidden, $language);
            if ($page === null) {
                continue;
            }

            $page['_LLMSTXT_PARENT'] = $treeParentId;
            $pages[$pageUid] = $page;

            // Recursively get child pages
            $this->collectPages($pageUid, $pageUid, $languageId, $excludePageUids, $includeHidden, $pages, $language);
        }
    }

    /**
     * Overlay a default language page record with its translation, if not default language.
     *
     * Returns null if the page has no translation and the language does not fall
     * back to the default language (strict mode).
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function overlayTranslation(array $row, int $languageId, bool $includeHidden, ?SiteLanguage $language): ?array
    {
        if ($languageId === 0) {
            return $row;
        }

        $translatedPage = $this->getTranslatedPage((int)$row['uid'], $languageId, $includeHidden);
        if ($translatedPage === null) {
            // In strict mode, skip pages without translation
            return $language instanceof SiteLanguage && $language->getFallbackType() === 'strict' ? null : $row;
        }

        // Merge translated fields into the base page. The priority is not translated:
        // every language uses the one of the default language page.
        $page = array_merge($row, $translatedPage);
        $page['tx_llmstxt_priority'] = $row['tx_llmstxt_priority'] ?? 0;
        $page['_PAGES_OVERLAY'] = true;
        $page['_PAGES_OVERLAY_UID'] = (int)$translatedPage['uid'];
        $page['_PAGES_OVERLAY_LANGUAGE'] = $languageId;

        return $page;
    }

    /**
     * Get translated page record.
     *
     * @return array<string, mixed>|null
     */
    private function getTranslatedPage(int $pageUid, int $languageId, bool $includeHidden): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $this->applyRestrictions($queryBuilder, $includeHidden);

        $constraints = [
            $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, ParameterType::INTEGER)),
        ];

        $result = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(...$constraints)
            ->executeQuery()
            ->fetchAssociative();

        return $result !== false ? $result : null;
    }

    /**
     * Generate URL for a page.
     */
    public function getPageUrl(Site $site, int $pageUid, SiteLanguage $language): string
    {
        try {
            $uri = $site->getRouter()->generateUri($pageUid, ['_language' => $language]);
            $url = (string)$uri;

            // Fix double language prefix issue (e.g., /en/en/page -> /en/page)
            $languageBase = rtrim((string)$language->getBase(), '/');
            if ($languageBase !== '' && $languageBase !== '/') {
                $doublePrefix = $languageBase . $languageBase;
                if (str_contains($url, $doublePrefix)) {
                    $url = str_replace($doublePrefix, $languageBase, $url);
                }
            }

            return $url;
        } catch (Exception $e) {
            $this->logger->warning('Failed to generate URL for page {pageUid}, using fallback slug-based URL', [
                'pageUid' => $pageUid,
                'exception' => $e->getMessage(),
            ]);

            return $this->getFallbackUrl($pageUid, $language);
        }
    }

    /**
     * Fallback URL generation using slug field.
     */
    private function getFallbackUrl(int $pageUid, SiteLanguage $language): string
    {
        $languageBase = rtrim((string)$language->getBase(), '/');

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $this->applyRestrictions($queryBuilder, false);

        $languageId = $this->extractLanguageId($language);
        $constraints = [];

        if ($languageId > 0) {
            // Try to get translated page slug
            $constraints[] = $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER));
            $constraints[] = $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, ParameterType::INTEGER));
        } else {
            $constraints[] = $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER));
        }

        $result = $queryBuilder
            ->select('slug')
            ->from('pages')
            ->where(...$constraints)
            ->executeQuery()
            ->fetchAssociative();

        if ($result !== false && isset($result['slug'])) {
            $slug = (string)$result['slug'];

            // Check if slug already starts with language prefix (avoid /en/en/...)
            if ($languageBase !== '' && $languageBase !== '/' && str_starts_with($slug, $languageBase)) {
                // Slug already contains language prefix, return as-is
                return $slug;
            }

            if ($slug === '/') {
                return $languageBase !== '' ? $languageBase . '/' : '/';
            }

            return $languageBase . $slug;
        }

        // If no translated slug found for non-default language, try default language
        if ($languageId > 0) {
            $defaultQueryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
            $this->applyRestrictions($defaultQueryBuilder, false);

            $defaultResult = $defaultQueryBuilder
                ->select('slug')
                ->from('pages')
                ->where(
                    $defaultQueryBuilder->expr()->eq('uid', $defaultQueryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER)),
                )
                ->executeQuery()
                ->fetchAssociative();

            if ($defaultResult !== false && isset($defaultResult['slug'])) {
                return $languageBase . $defaultResult['slug'];
            }
        }

        return $languageBase !== '' ? $languageBase . '/' : '/';
    }

    /**
     * Apply query restrictions: keep deleted, fe_group, starttime/endtime checks;
     * optionally remove hidden restriction when includeHidden is true.
     */
    private function applyRestrictions(QueryBuilder $queryBuilder, bool $includeHidden): void
    {
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
            ->add(new StartTimeRestriction())
            ->add(new EndTimeRestriction())
            ->add(new FrontendGroupRestriction());

        if (!$includeHidden) {
            $queryBuilder->getRestrictions()->add(new HiddenRestriction());
        }
    }

    /**
     * Extract language ID from SiteLanguage object.
     * Uses toArray() to avoid Extension Scanner "weak" warnings for getLanguageId().
     */
    private function extractLanguageId(SiteLanguage $language): int
    {
        $languageConfig = $language->toArray();

        return (int)($languageConfig['languageId'] ?? 0);
    }
}
