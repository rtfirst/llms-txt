<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RTfirst\LlmsTxt\Utility\LlmsTxtPath;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main service for generating llms.txt content.
 *
 * Content is served dynamically via LlmsTxtMiddleware with API key protection.
 */
final readonly class LlmsTxtGeneratorService
{
    public function __construct(
        private SiteFinder $siteFinder,
        private PageTreeService $pageTreeService,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get llms.txt content for a site language.
     *
     * Every language has its own llms.txt below its base (/llms.txt, /en/llms.txt, ...),
     * which lists the pages in this language and links to the llms.txt of the other
     * languages. Without a language, the default language is used.
     */
    public function getContentForSite(Site $site, ?SiteLanguage $language = null): string
    {
        $language ??= $site->getDefaultLanguage();
        $settings = $this->getSettings($site);
        $excludePages = $this->parseExcludePages($settings['excludePages'] ?? '');
        $includeHidden = (bool)($settings['includeHidden'] ?? false);
        $enableMarkdown = (bool)($settings['enableMarkdown'] ?? true);

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

            return '';
        }

        $baseUrl = $this->getBaseUrl($language);
        $intro = $this->getIntro($language, $settings);
        $languageLinks = $this->buildLanguageLinks($site, $language, $excludePages, $includeHidden);

        $apiKey = trim((string)($settings['apiKey'] ?? ''));

        return $this->buildContent($site, $language, $pages, $baseUrl, $intro, $apiKey, $enableMarkdown, $languageLinks);
    }

    /**
     * Get all site identifiers for cache invalidation.
     *
     * @return array<string>
     */
    public function getAllSiteIdentifiers(): array
    {
        $sites = $this->siteFinder->getAllSites();
        $identifiers = [];

        foreach ($sites as $site) {
            $identifiers[] = $site->getIdentifier();
        }

        return $identifiers;
    }

    /**
     * Build the llms.txt content.
     *
     * @param array<int, array<string, mixed>> $pages
     * @param list<string> $languageLinks
     */
    private function buildContent(
        Site $site,
        SiteLanguage $language,
        array $pages,
        string $baseUrl,
        string $intro,
        string $apiKey,
        bool $enableMarkdown,
        array $languageLinks,
    ): string {
        $lines = [];

        // Order pages as a tree, siblings by priority (higher first), then by original order
        $pageDepths = $this->orderPagesAsTree($pages);

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
        $lines[] = '**Specification:** <https://llmstxt.org/>';
        $lines[] = '**Domain:** ' . $baseUrl;
        $lines[] = '**Language:** ' . $language->getLocale()->getLanguageCode();
        $lines[] = '**Generated:** ' . date('Y-m-d H:i:s');
        $lines[] = '';

        // LLM-optimized content access hints (spec-compliant with llmstxt.org)
        // Plain text without headings: llmstxt.org parsers read every "##" line,
        // including "###", as the start of a file list section.
        // Omitted entirely when Markdown output is disabled for this site, including
        // the authentication hints: llms.txt is then the only protected endpoint, and
        // whoever can read these hints has already authenticated for it.
        if ($enableMarkdown) {
            // Find an example page (first non-root page with a Markdown version)
            $examplePageUrl = $this->findExamplePageUrl($site, $pages, $pageDepths, $language);

            $lines[] = 'This site provides LLM-friendly Markdown output for all content pages.';
            $lines[] = '';
            $lines[] = '**Markdown Format:** Append `.md` to a page URL to get plain Markdown with YAML frontmatter.'
                . ' Pages that only link to another page or URL are listed without a Markdown link.';
            $lines[] = '- **Example:** `' . $this->buildMarkdownUrl($examplePageUrl) . '`';
            $lines[] = '';

            // Add authentication hints if API key is configured
            if ($apiKey !== '') {
                $lines[] = '**Authentication:** This site requires API key authentication for all LLM endpoints.';
                $lines[] = '';
                $lines[] = '**HTTP Header (recommended):**';
                $lines[] = '```';
                $lines[] = 'X-LLM-API-Key: <your-api-key>';
                $lines[] = '```';
                $lines[] = '';
                $lines[] = '**Query Parameter:**';
                $lines[] = '```';
                $lines[] = $baseUrl . '/page.md?api_key=<your-api-key>';
                $lines[] = '```';
                $lines[] = '';
            }
        }

        // Links to the llms.txt of the other languages
        if ($languageLinks !== []) {
            $lines[] = '## ' . $this->getTranslation('languages');
            $lines[] = '';
            array_push($lines, ...$languageLinks);
            $lines[] = '';
        }

        // Page structure with descriptions (page tree, siblings sorted by priority)
        $lines[] = '## ' . $this->getTranslation('pageStructure');
        $lines[] = '';

        // Build tree structure for display, one line per page as llmstxt.org
        // defines it: "- [name](url): notes"
        foreach ($pageDepths as $pageUid => $indent) {
            $page = $pages[$pageUid];
            $pageTitle = (string)($page['title'] ?? '');
            $pageUrl = $this->pageTreeService->getPageUrl($site, $pageUid, $language);

            $line = str_repeat('  ', $indent) . '- [' . $this->escapeLinkText($pageTitle) . '](' . $pageUrl . ')';

            $notes = $this->buildPageNotes($page, $pageUrl, $enableMarkdown);
            if ($notes !== '') {
                $line .= ': ' . $notes;
            }
            $lines[] = $line;
        }
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Build the notes after a page link: description, summary, keywords and the
     * Markdown link (unless disabled or the page has no Markdown version), joined
     * to a single line.
     *
     * @param array<string, mixed> $page
     */
    private function buildPageNotes(array $page, string $pageUrl, bool $enableMarkdown): string
    {
        $texts = [
            $this->getPageDescription($page),
            (string)($page['tx_llmstxt_summary'] ?? ''),
        ];

        $keywords = $this->toSingleLine((string)($page['tx_llmstxt_keywords'] ?? ''));
        if ($keywords !== '') {
            $texts[] = $this->getTranslation('keywords') . ': ' . $keywords;
        }

        $notes = [];
        foreach ($texts as $text) {
            $text = $this->toSingleLine($text);
            if ($text === '') {
                continue;
            }
            // End each text as a sentence, so the joined notes stay readable
            if (preg_match('/[.!?…]$/u', $text) !== 1) {
                $text .= '.';
            }
            $notes[] = $text;
        }

        // Add format access hint (spec-compliant .md suffix), unless disabled or
        // the page is a link or shortcut without content of its own
        if ($enableMarkdown && !$this->pageTreeService->isLinkOrShortcut($page)) {
            $notes[] = '[Markdown](' . $this->buildMarkdownUrl($pageUrl) . ')';
        }

        return implode(' ', $notes);
    }

    /**
     * Collapse line breaks and repeated whitespace, as a page entry must fit on one line.
     */
    private function toSingleLine(string $text): string
    {
        return trim((string)preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Order pages as a tree: every page is followed by its subpages, siblings
     * are sorted by priority (higher values first).
     *
     * @param array<int, array<string, mixed>> $pages Page records in page tree order
     * @return array<int, int> Depth of each page, indexed by UID, in output order
     */
    private function orderPagesAsTree(array $pages): array
    {
        $children = [];
        foreach ($pages as $pageUid => $page) {
            // Spacers are not listed, PageTreeService provides the parent above them
            $parentUid = (int)($page['_LLMSTXT_PARENT'] ?? $page['pid'] ?? 0);
            // Pages without a listed parent (site root, excluded root page) are top-level
            $children[isset($pages[$parentUid]) ? $parentUid : 0][] = $pageUid;
        }

        $depths = [];
        $this->addBranch(0, 0, $children, $pages, $depths);

        return $depths;
    }

    /**
     * Add the subpages of a page and their branches to the tree order.
     *
     * @param array<int, list<int>> $children Subpage UIDs by parent UID, in page tree order
     * @param array<int, array<string, mixed>> $pages
     * @param array<int, int> $depths
     */
    private function addBranch(int $parentUid, int $depth, array $children, array $pages, array &$depths): void
    {
        $siblings = $children[$parentUid] ?? [];

        // Higher priority first; usort is stable, so equal priorities keep the page tree order
        usort($siblings, static fn(int $a, int $b): int => (int)($pages[$b]['tx_llmstxt_priority'] ?? 0) <=> (int)($pages[$a]['tx_llmstxt_priority'] ?? 0));

        foreach ($siblings as $pageUid) {
            $depths[$pageUid] = $depth;
            $this->addBranch($pageUid, $depth + 1, $children, $pages, $depths);
        }
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
     * Find a suitable example page URL for documentation.
     *
     * Returns the URL of the first non-root page with a Markdown version, or the
     * root page URL if there is no such page.
     *
     * @param array<int, array<string, mixed>> $pages
     * @param array<int, int> $pageDepths Depth of each page, indexed by UID, in output order
     */
    private function findExamplePageUrl(Site $site, array $pages, array $pageDepths, SiteLanguage $language): string
    {
        $rootPageId = $site->getRootPageId();

        // Find first non-root page, skipping links and shortcuts
        foreach (array_keys($pageDepths) as $pageUid) {
            if ($pageUid !== $rootPageId && !$this->pageTreeService->isLinkOrShortcut($pages[$pageUid])) {
                return $this->pageTreeService->getPageUrl($site, $pageUid, $language);
            }
        }

        // Fallback to root page if no other page has a Markdown version
        return $this->pageTreeService->getPageUrl($site, $rootPageId, $language);
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
     * Get the base URL of a language, without trailing slash (e.g. https://example.com/en).
     */
    private function getBaseUrl(SiteLanguage $language): string
    {
        // 1. The language base already contains the site base if that is a full URL
        $languageBase = $language->getBase();
        if ($languageBase->getScheme() !== '' && $languageBase->getHost() !== '') {
            return rtrim((string)$languageBase, '/');
        }

        $basePath = rtrim($languageBase->getPath(), '/');

        // 2. Try TYPO3_REQUEST_HOST environment variable
        $requestHost = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
        if (\is_string($requestHost) && $requestHost !== '' && $requestHost !== 'http:') {
            return rtrim($requestHost, '/') . $basePath;
        }

        // 3. Fallback: use relative path only
        return $basePath;
    }

    /**
     * Get the intro of the llms.txt of a language.
     *
     * The intro of the language in the site configuration comes first. The site
     * setting llmsTxt.intro is the fallback for the default language only, as it
     * is written in one language.
     *
     * @param array<string, mixed> $settings
     */
    private function getIntro(SiteLanguage $language, array $settings): string
    {
        $languageIntro = trim((string)($language->toArray()['llmsTxtIntro'] ?? ''));
        if ($languageIntro !== '') {
            return $languageIntro;
        }

        if ($this->extractLanguageId($language) === 0) {
            return trim((string)($settings['intro'] ?? ''));
        }

        return '';
    }

    /**
     * Build the links to the llms.txt of the other enabled languages of the site.
     *
     * Languages without pages are left out, as their llms.txt does not exist.
     *
     * @param array<int> $excludePages
     * @return list<string>
     */
    private function buildLanguageLinks(Site $site, SiteLanguage $currentLanguage, array $excludePages, bool $includeHidden): array
    {
        $currentLanguageId = $this->extractLanguageId($currentLanguage);
        $links = [];

        foreach ($site->getLanguages() as $language) {
            if ($this->extractLanguageId($language) === $currentLanguageId) {
                continue;
            }
            if ($this->pageTreeService->getPages($site, $language, $excludePages, $includeHidden) === []) {
                continue;
            }

            $url = $this->getBaseUrl($language) . '/' . LlmsTxtPath::FILE_NAME;
            $links[] = '- [' . $this->escapeLinkText($language->getNavigationTitle()) . '](' . $url . '): '
                . $language->getLocale()->getLanguageCode();
        }

        return $links;
    }

    /**
     * Escape Markdown link syntax in a link text.
     *
     * Brackets become entities, as llmstxt.org parsers end the link name at the
     * first "]", even at "\]".
     */
    private function escapeLinkText(string $text): string
    {
        return str_replace(['[', ']', '(', ')'], ['&#91;', '&#93;', '\\(', '\\)'], $text);
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
            'languages' => 'Languages',
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
