<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Service;

use Doctrine\DBAL\ParameterType;
use Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
    ) {}

    /**
     * Get all visible pages for a site in a specific language.
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
        $rootPage = $this->getPage($rootPageId, $languageId, $includeHidden);
        if ($rootPage !== null && !\in_array($rootPageId, $excludePageUids, true)) {
            $pages[$rootPageId] = $rootPage;
        }

        // Then collect all child pages recursively
        $this->collectPages($rootPageId, $languageId, $excludePageUids, $includeHidden, $pages, $language);

        return $pages;
    }

    /**
     * Get a single page record.
     *
     * @return array<string, mixed>|null
     */
    private function getPage(int $pageUid, int $languageId, bool $includeHidden): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $constraints = [
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('deleted', 0),
            $queryBuilder->expr()->eq('tx_llmstxt_exclude', 0),
        ];

        if (!$includeHidden) {
            $constraints[] = $queryBuilder->expr()->eq('hidden', 0);
        }

        $row = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(...$constraints)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        // Get translation if not default language
        if ($languageId > 0) {
            $translatedPage = $this->getTranslatedPage($pageUid, $languageId, $includeHidden);
            if ($translatedPage !== null) {
                $row = array_merge($row, $translatedPage);
                $row['_PAGES_OVERLAY'] = true;
                $row['_PAGES_OVERLAY_UID'] = (int)$translatedPage['uid'];
                $row['_PAGES_OVERLAY_LANGUAGE'] = $languageId;
            }
        }

        return $row;
    }

    /**
     * Recursively collect pages from the page tree.
     *
     * @param array<int> $excludePageUids
     * @param array<int, array<string, mixed>> $pages
     */
    private function collectPages(
        int $parentId,
        int $languageId,
        array $excludePageUids,
        bool $includeHidden,
        array &$pages,
        ?SiteLanguage $language = null,
    ): void {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        // Base constraints
        $constraints = [
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($parentId, ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('deleted', 0),
            $queryBuilder->expr()->eq('sys_language_uid', 0), // Always query default language first
            $queryBuilder->expr()->eq('tx_llmstxt_exclude', 0), // Exclude pages marked for exclusion
        ];

        if (!$includeHidden) {
            $constraints[] = $queryBuilder->expr()->eq('hidden', 0);
        }

        // Exclude certain doktypes (folders, recycler, etc.)
        // doktype values: 254=sysfolder, 255=recycler, 199=spacer, 6=be_user_section
        $excludedDoktypes = [
            255, // Recycler
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_SPACER,
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

            // Get translated page if not default language
            if ($languageId > 0) {
                $translatedPage = $this->getTranslatedPage($pageUid, $languageId, $includeHidden);
                if ($translatedPage !== null) {
                    // Merge translated fields into the base page
                    $row = array_merge($row, $translatedPage);
                    $row['_PAGES_OVERLAY'] = true;
                    $row['_PAGES_OVERLAY_UID'] = (int)$translatedPage['uid'];
                    $row['_PAGES_OVERLAY_LANGUAGE'] = $languageId;
                } elseif ($language instanceof SiteLanguage && $language->getFallbackType() === 'strict') {
                    // In strict mode, skip pages without translation
                    continue;
                }
            }

            $pages[$pageUid] = $row;

            // Recursively get child pages
            $this->collectPages($pageUid, $languageId, $excludePageUids, $includeHidden, $pages, $language);
        }
    }

    /**
     * Get translated page record.
     *
     * @return array<string, mixed>|null
     */
    private function getTranslatedPage(int $pageUid, int $languageId, bool $includeHidden): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $constraints = [
            $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('deleted', 0),
        ];

        if (!$includeHidden) {
            $constraints[] = $queryBuilder->expr()->eq('hidden', 0);
        }

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
        } catch (Exception) {
            // Fallback to base URL + slug
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
        $queryBuilder->getRestrictions()->removeAll();

        $languageId = $this->extractLanguageId($language);
        $constraints = [
            $queryBuilder->expr()->eq('deleted', 0),
        ];

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
            $defaultQueryBuilder->getRestrictions()->removeAll();

            $defaultResult = $defaultQueryBuilder
                ->select('slug')
                ->from('pages')
                ->where(
                    $defaultQueryBuilder->expr()->eq('uid', $defaultQueryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER)),
                    $defaultQueryBuilder->expr()->eq('deleted', 0),
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
     * Extract language ID from SiteLanguage object.
     * Uses toArray() to avoid Extension Scanner "weak" warnings for getLanguageId().
     */
    private function extractLanguageId(SiteLanguage $language): int
    {
        $languageConfig = $language->toArray();

        return (int)($languageConfig['languageId'] ?? 0);
    }
}
