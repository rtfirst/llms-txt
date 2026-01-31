<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Service;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Service for fetching content elements from pages.
 */
final readonly class ContentFetcherService
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * Get all visible content elements for a page in a specific language.
     *
     * @return array<int, array<string, mixed>> Content records sorted by colPos and sorting
     */
    public function getContentElements(int $pageUid, SiteLanguage $language): array
    {
        $languageId = $this->extractLanguageId($language);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $constraints = [
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('deleted', 0),
            $queryBuilder->expr()->eq('hidden', 0),
        ];

        if ($languageId > 0) {
            // For non-default languages, get translated content
            // Language modes: -1 = all languages, 0 = default, >0 = specific language
            $constraints[] = $queryBuilder->expr()->or(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('sys_language_uid', -1), // "All languages" content
            );
        } else {
            // Default language: get elements with sys_language_uid 0 or -1
            $constraints[] = $queryBuilder->expr()->in('sys_language_uid', [0, -1]);
        }

        $result = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(...$constraints)
            ->orderBy('colPos', 'ASC')
            ->addOrderBy('sorting', 'ASC')
            ->executeQuery();

        $contentElements = [];
        while ($row = $result->fetchAssociative()) {
            $contentElements[] = $row;
        }

        return $contentElements;
    }

    /**
     * Get content elements for a specific column position.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getContentElementsForColumn(int $pageUid, SiteLanguage $language, int $colPos): array
    {
        $allContent = $this->getContentElements($pageUid, $language);

        return array_filter(
            $allContent,
            static fn(array $record): bool => (int)($record['colPos'] ?? 0) === $colPos,
        );
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
