<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main service for generating llms.txt files.
 */
final readonly class LlmsTxtGeneratorService
{
    public function __construct(
        private SiteFinder $siteFinder,
        private PageTreeService $pageTreeService,
        private LoggerInterface $logger,
    ) {}

    /**
     * Generate llms.txt files for all sites and languages.
     */
    public function generate(): void
    {
        $sites = $this->siteFinder->getAllSites();

        foreach ($sites as $site) {
            $this->generateForSite($site);
        }
    }

    /**
     * Generate llms.txt for a specific site (default language only).
     *
     * Only generates a single llms.txt file for the default language (ID 0).
     * Multi-language content is accessible via ?format=clean or ?format=md
     * on any page URL with the appropriate language prefix.
     */
    public function generateForSite(Site $site): void
    {
        // Only generate for default language (ID 0)
        $defaultLanguage = $site->getDefaultLanguage();

        try {
            $this->generateForLanguage($site, $defaultLanguage);
        } catch (Exception $e) {
            $this->logger->log(
                LogLevel::ERROR,
                'Failed to generate llms.txt for site {site}: {message}',
                [
                    'site' => $site->getIdentifier(),
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ],
            );
        }
    }

    /**
     * Generate llms.txt for a specific site and language.
     */
    private function generateForLanguage(Site $site, SiteLanguage $language): void
    {
        $settings = $this->getSettings($site);
        $excludePages = $this->parseExcludePages($settings['excludePages'] ?? '');
        $includeHidden = (bool)($settings['includeHidden'] ?? false);
        $intro = trim((string)($settings['intro'] ?? ''));

        $pages = $this->pageTreeService->getPages($site, $language, $excludePages, $includeHidden);

        if ($pages === []) {
            $this->logger->log(
                LogLevel::INFO,
                'No pages found for site {site} language {language}',
                [
                    'site' => $site->getIdentifier(),
                    'language' => $language->getLocale()->getLanguageCode(),
                ],
            );

            return;
        }

        $baseUrl = $this->getBaseUrl($site, $language);

        $content = $this->buildContent($site, $language, $pages, $baseUrl, $intro);
        $filename = $this->getFilename();
        $this->writeFile($filename, $content);

        $this->logger->log(
            LogLevel::INFO,
            'Generated {filename} for site {site}',
            [
                'filename' => $filename,
                'site' => $site->getIdentifier(),
            ],
        );
    }

    /**
     * Build the llms.txt content.
     *
     * @param array<int, array<string, mixed>> $pages
     */
    private function buildContent(
        Site $site,
        SiteLanguage $language,
        array $pages,
        string $baseUrl,
        string $intro,
    ): string {
        $lines = [];

        // Sort pages by priority (higher first), then by original order
        $sortedPages = $this->sortPagesByPriority($pages);

        // Site title
        $rootPage = $pages[$site->getRootPageId()] ?? reset($pages);
        $siteTitle = (string)($rootPage['title'] ?? $site->getIdentifier());
        $lines[] = '# ' . $siteTitle;
        $lines[] = '';

        // Intro text if configured
        if ($intro !== '') {
            $lines[] = '> ' . str_replace("\n", "\n> ", $intro);
            $lines[] = '';
        }

        // Project metadata
        $lines[] = '**Domain:** ' . $baseUrl;
        $lines[] = '**Language:** ' . $language->getLocale()->getLanguageCode();
        $lines[] = '**Generated:** ' . date('Y-m-d H:i:s');
        $lines[] = '';

        // LLM-optimized content access section (spec-compliant with llmstxt.org)
        $lines[] = '## LLM-Optimized Content Access';
        $lines[] = '';
        $lines[] = 'This site provides LLM-friendly output formats for all pages:';
        $lines[] = '';
        $lines[] = '### Markdown (Recommended)';
        $lines[] = 'Append `.md` to any page URL to get plain Markdown with YAML frontmatter.';
        $lines[] = '- **Example:** `' . $baseUrl . '/page-slug.md`';
        $lines[] = '- **Alternative:** `?format=md` query parameter';
        $lines[] = '';
        $lines[] = '### Clean HTML';
        $lines[] = 'Semantic HTML without CSS/JS/navigation. Best for RAG systems.';
        $lines[] = '- **URL-Parameter:** `?format=clean`';
        $lines[] = '- **Example:** `' . $baseUrl . '/page-slug/?format=clean`';
        $lines[] = '';
        $lines[] = '### Multi-Language Access';
        $lines[] = 'Use language-specific URL prefixes with the `.md` suffix:';
        $lines[] = '- **Default language:** `' . $baseUrl . '/page.md`';
        $lines[] = '- **English:** `' . $baseUrl . '/en/page.md`';
        $lines[] = '- **Other languages:** Use configured prefix (e.g., `/de/page.md`, `/fr/page.md`)';
        $lines[] = '';

        // Page structure with descriptions (sorted by priority for display)
        $lines[] = '## ' . $this->getTranslation('pageStructure');
        $lines[] = '';

        // Build tree structure for display
        foreach ($sortedPages as $pageUid => $page) {
            $pageTitle = (string)($page['title'] ?? '');
            $pageUrl = $this->pageTreeService->getPageUrl($site, $pageUid, $language);
            $indent = $this->getIndentLevel($page, $pages);
            $priority = (int)($page['tx_llmstxt_priority'] ?? 0);

            // Page entry with URL
            $lines[] = str_repeat('  ', $indent) . '- **[' . $pageTitle . '](' . $pageUrl . ')**';

            // Add description if available
            $description = $this->getPageDescription($page);
            if ($description !== '') {
                $lines[] = str_repeat('  ', $indent) . '  ' . $description;
            }

            // Add keywords if available
            $keywords = trim((string)($page['tx_llmstxt_keywords'] ?? ''));
            if ($keywords !== '') {
                $lines[] = str_repeat('  ', $indent) . '  *' . $this->getTranslation('keywords') . ': ' . $keywords . '*';
            }

            // Add custom summary if available
            $summary = trim((string)($page['tx_llmstxt_summary'] ?? ''));
            if ($summary !== '') {
                $lines[] = str_repeat('  ', $indent) . '  > ' . str_replace("\n", ' ', $summary);
            }

            // Add format access hints (spec-compliant .md suffix)
            $mdUrl = $this->buildMarkdownUrl($pageUrl);
            $lines[] = str_repeat('  ', $indent) . '  [Markdown](' . $mdUrl . ') | [Clean HTML](' . $pageUrl . '?format=clean)';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Sort pages by priority (higher values first).
     *
     * @param array<int, array<string, mixed>> $pages
     * @return array<int, array<string, mixed>>
     */
    private function sortPagesByPriority(array $pages): array
    {
        $sortedPages = $pages;
        uasort($sortedPages, static function (array $a, array $b): int {
            $priorityA = (int)($a['tx_llmstxt_priority'] ?? 0);
            $priorityB = (int)($b['tx_llmstxt_priority'] ?? 0);

            // Higher priority first
            return $priorityB <=> $priorityA;
        });

        return $sortedPages;
    }

    /**
     * Get the description for a page (LLM description or fallback to meta description).
     *
     * @param array<string, mixed> $page
     */
    private function getPageDescription(array $page): string
    {
        // Prefer LLM-specific description
        $llmDescription = trim((string)($page['tx_llmstxt_description'] ?? ''));
        if ($llmDescription !== '') {
            return $llmDescription;
        }

        // Fallback to meta description
        $metaDescription = trim((string)($page['description'] ?? ''));
        if ($metaDescription !== '') {
            return $metaDescription;
        }

        // Fallback to abstract
        return trim((string)($page['abstract'] ?? ''));
    }

    /**
     * Calculate indent level for page in tree structure.
     *
     * @param array<string, mixed> $page
     * @param array<int, array<string, mixed>> $pages
     */
    private function getIndentLevel(array $page, array $pages): int
    {
        $level = 0;
        $pid = (int)($page['pid'] ?? 0);

        while (isset($pages[$pid])) {
            $level++;
            $pid = (int)($pages[$pid]['pid'] ?? 0);
        }

        return $level;
    }

    /**
     * Build a markdown URL by appending .md suffix (spec-compliant).
     *
     * Transforms:
     * - /page/ -> /page.md
     * - /page -> /page.md
     * - / -> /index.html.md
     */
    private function buildMarkdownUrl(string $pageUrl): string
    {
        // Parse URL to handle base URL and path separately
        $parsedUrl = parse_url($pageUrl);
        $path = $parsedUrl['path'] ?? '/';

        // Handle root path or remove trailing slash and append .md
        $mdPath = $path === '/' || $path === '' ? '/index.html.md' : rtrim($path, '/') . '.md';

        // Reconstruct URL
        $baseUrl = '';
        if (isset($parsedUrl['scheme'], $parsedUrl['host'])) {
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $baseUrl .= ':' . $parsedUrl['port'];
            }
        }

        return $baseUrl . $mdPath;
    }

    /**
     * Get the filename for llms.txt (always the same, regardless of language).
     */
    private function getFilename(): string
    {
        return 'llms.txt';
    }

    /**
     * Get the base URL for a site and language.
     */
    private function getBaseUrl(Site $site, SiteLanguage $language): string
    {
        $languageId = $this->extractLanguageId($language);
        $languageBase = (string)$language->getBase();
        $languagePrefix = '';
        if ($languageId > 0 && $languageBase !== '/' && $languageBase !== '') {
            $languagePrefix = '/' . trim($languageBase, '/');
        }

        // 1. Check for configured base URL in site settings
        $settings = $this->getSettings($site);
        $configuredBaseUrl = trim((string)($settings['baseUrl'] ?? ''));
        if ($configuredBaseUrl !== '') {
            return rtrim($configuredBaseUrl, '/') . $languagePrefix;
        }

        // 2. Try site base if it's a full URL
        $siteBase = (string)$site->getBase();
        if (str_starts_with($siteBase, 'http://') || str_starts_with($siteBase, 'https://')) {
            return rtrim($siteBase, '/') . $languagePrefix;
        }

        // 3. Try TYPO3_REQUEST_HOST environment variable
        $requestHost = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
        if (\is_string($requestHost) && $requestHost !== '' && $requestHost !== 'http:') {
            return rtrim($requestHost, '/') . $languagePrefix;
        }

        // 4. Fallback: use relative path only
        return $languagePrefix;
    }

    /**
     * Write content to file in public directory.
     * Prepends UTF-8 BOM for proper encoding detection in browsers.
     */
    private function writeFile(string $filename, string $content): void
    {
        $publicPath = Environment::getPublicPath();
        $filePath = $publicPath . '/' . $filename;

        // Add UTF-8 BOM (Byte Order Mark) for proper encoding detection
        $utf8Bom = "\xEF\xBB\xBF";
        GeneralUtility::writeFile($filePath, $utf8Bom . $content);
    }

    /**
     * Get settings from site configuration.
     *
     * @return array<string, mixed>
     */
    private function getSettings(Site $site): array
    {
        $settings = $site->getSettings()->getAll();

        return $settings['llmsTxt'] ?? [];
    }

    /**
     * Parse comma-separated list of page UIDs to exclude.
     *
     * @return array<int>
     */
    private function parseExcludePages(string $excludePages): array
    {
        if ($excludePages === '') {
            return [];
        }

        $uids = GeneralUtility::intExplode(',', $excludePages, true);

        return array_filter($uids, static fn(int $uid): bool => $uid > 0);
    }

    /**
     * Get translation for a key (English labels for llms.txt output).
     */
    private function getTranslation(string $key): string
    {
        // llms.txt is always generated in English for international compatibility
        $translations = [
            'pageStructure' => 'Page Structure',
            'keywords' => 'Keywords',
        ];

        return $translations[$key] ?? $key;
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
