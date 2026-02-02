<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Service;

use League\HTMLToMarkdown\HtmlConverter;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Service to render page content as Markdown with YAML frontmatter.
 */
final class MarkdownRendererService
{
    private ?HtmlConverter $htmlConverter = null;

    /**
     * Render page content as Markdown with YAML frontmatter.
     */
    public function render(
        string $originalHtml,
        int $pageId,
        ?SiteLanguage $language,
        string $baseUrl,
    ): string {
        // Extract metadata from original HTML
        $title = $this->extractTitle($originalHtml);
        $description = $this->extractMetaDescription($originalHtml);
        $canonical = $this->extractCanonical($originalHtml);
        $author = $this->extractAuthor($originalHtml);

        // Make canonical URL absolute if it's relative
        if ($canonical !== '' && !str_starts_with($canonical, 'http')) {
            $canonical = rtrim($baseUrl, '/') . '/' . ltrim($canonical, '/');
        }

        // Extract and convert main content
        $mainContent = $this->extractMainContent($originalHtml);
        $markdownContent = $this->convertToMarkdown($mainContent, $baseUrl);

        // Build Markdown document with YAML frontmatter
        $lines = [];
        $lines[] = '---';
        $lines[] = 'title: "' . $this->escapeYaml($title) . '"';

        if ($description !== '') {
            $lines[] = 'description: "' . $this->escapeYaml($description) . '"';
        }

        if ($author !== '') {
            $lines[] = 'author: "' . $this->escapeYaml($author) . '"';
        }

        $lines[] = 'language: ' . ($language?->getLocale()->getLanguageCode() ?? 'de');
        $lines[] = 'date: ' . date('Y-m-d');

        if ($canonical !== '') {
            $lines[] = 'canonical: "' . $canonical . '"';
        }

        $lines[] = 'format: markdown';
        $lines[] = 'generator: "TYPO3 LLMs.txt Extension"';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = '# ' . $title;
        $lines[] = '';

        if ($description !== '') {
            $lines[] = '> ' . $description;
            $lines[] = '';
        }

        $lines[] = $markdownContent;

        // Add UTF-8 BOM for proper encoding detection
        return "\xEF\xBB\xBF" . implode("\n", $lines);
    }

    /**
     * Extract title from HTML.
     */
    private function extractTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            $title = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
            // Remove site name suffix if present (e.g., "Page Title | Site Name")
            $title = preg_replace('/\s*[|–-]\s*[^|–-]+$/', '', $title) ?? $title;
            return $title;
        }

        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
            return trim(strip_tags($matches[1]));
        }

        return 'Untitled';
    }

    /**
     * Extract meta description from HTML.
     */
    private function extractMetaDescription(string $html): string
    {
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }

        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\'][^>]*>/i', $html, $matches)) {
            return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }

        return '';
    }

    /**
     * Extract canonical URL from HTML.
     */
    private function extractCanonical(string $html): string
    {
        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical["\'][^>]*>/i', $html, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Extract author from HTML meta tag.
     */
    private function extractAuthor(string $html): string
    {
        if (preg_match('/<meta[^>]+name=["\']author["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }

        return '';
    }

    /**
     * Extract main content from HTML.
     */
    private function extractMainContent(string $html): string
    {
        $content = $html;

        // Remove script and style tags
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content) ?? $content;
        $content = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $content) ?? $content;

        // Remove visually-hidden spans (Bootstrap accessibility elements like "Previous" and "Next")
        $content = preg_replace('/<span\b[^>]*class=["\'][^"\']*visually-hidden[^"\']*["\'][^>]*>.*?<\/span>/is', '', $content) ?? $content;

        // Remove empty anchor tags (TYPO3 content element anchors like <a id="c1"></a>)
        $content = preg_replace('/<a\b[^>]*id=["\'][^"\']*["\'][^>]*>\s*<\/a>/is', '', $content) ?? $content;

        // Remove navigation elements
        $content = preg_replace('/<nav\b[^>]*>.*?<\/nav>/is', '', $content) ?? $content;
        $content = preg_replace('/<header\b[^>]*class=["\'][^"\']*navbar[^"\']*["\'][^>]*>.*?<\/header>/is', '', $content) ?? $content;

        // Remove footer
        $content = preg_replace('/<footer\b[^>]*>.*?<\/footer>/is', '', $content) ?? $content;

        // Remove aside (sidebars)
        $content = preg_replace('/<aside\b[^>]*>.*?<\/aside>/is', '', $content) ?? $content;

        // Try to extract main or article content
        if (preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $content, $matches)) {
            $content = $matches[1];
        } elseif (preg_match('/<article\b[^>]*>(.*?)<\/article>/is', $content, $matches)) {
            $content = $matches[1];
        } elseif (preg_match('/<div\b[^>]*class=["\'][^"\']*(?:content|main)[^"\']*["\'][^>]*>(.*?)<\/div>/is', $content, $matches)) {
            $content = $matches[1];
        }

        return $content;
    }

    /**
     * Convert HTML to Markdown.
     */
    private function convertToMarkdown(string $html, string $baseUrl): string
    {
        if ($html === '') {
            return '';
        }

        if (!$this->htmlConverter instanceof HtmlConverter) {
            $this->htmlConverter = new HtmlConverter([
                'strip_tags' => true,
                'remove_nodes' => 'script style nav',
                'hard_break' => false,
                'header_style' => 'atx',
            ]);
        }

        $markdown = $this->htmlConverter->convert($html);

        // Clean up the markdown
        $markdown = $this->cleanupMarkdown($markdown);

        // Convert relative links to absolute URLs
        $markdown = $this->convertRelativeLinksToAbsolute($markdown, $baseUrl);

        return trim($markdown);
    }

    /**
     * Clean up markdown output - remove excessive whitespace, empty lines, and HTML remnants.
     */
    private function cleanupMarkdown(string $markdown): string
    {
        // Normalize line endings
        $markdown = str_replace("\r\n", "\n", $markdown);
        $markdown = str_replace("\r", "\n", $markdown);

        // Convert remaining HTML tags to Markdown before stripping
        // Convert <br> tags to newlines
        $markdown = preg_replace('/<br\s*\/?>/i', "\n", $markdown) ?? $markdown;

        // Convert <a> tags to Markdown links [text](url)
        $markdown = preg_replace_callback(
            '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>([^<]*)<\/a>/i',
            static fn(array $matches): string => '[' . trim($matches[2]) . '](' . $matches[1] . ')',
            $markdown,
        ) ?? $markdown;

        // Convert <strong> and <b> to **bold**
        $markdown = preg_replace('/<(?:strong|b)\b[^>]*>([^<]*)<\/(?:strong|b)>/i', '**$1**', $markdown) ?? $markdown;

        // Convert <em> and <i> to *italic*
        $markdown = preg_replace('/<(?:em|i)\b[^>]*>([^<]*)<\/(?:em|i)>/i', '*$1*', $markdown) ?? $markdown;

        // Strip any remaining HTML tags
        $markdown = strip_tags($markdown);

        // Decode HTML entities (e.g., &amp; → &, &lt; → <)
        $markdown = html_entity_decode($markdown, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Fix header formatting (remove extra spaces after #)
        $markdown = preg_replace('/^(#{1,6})\s+/m', '$1 ', $markdown) ?? $markdown;

        // Remove trailing whitespace from each line
        $lines = explode("\n", $markdown);
        $lines = array_map(rtrim(...), $lines);

        // Remove excessive empty lines (keep max 1 empty line between blocks)
        $result = [];
        $lastWasEmpty = false;

        foreach ($lines as $line) {
            $isEmpty = $line === '';

            if ($isEmpty) {
                if (!$lastWasEmpty) {
                    $result[] = '';
                    $lastWasEmpty = true;
                }
                // Skip additional empty lines
            } else {
                $result[] = $line;
                $lastWasEmpty = false;
            }
        }

        return implode("\n", $result);
    }

    /**
     * Escape special characters for YAML string values.
     */
    private function escapeYaml(string $value): string
    {
        // Escape quotes and backslashes
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    /**
     * Convert relative links and image sources to absolute URLs.
     * Internal page links get .md suffix for consistent LLM navigation.
     *
     * Handles:
     * - Markdown links: [text](/path) -> [text](https://example.com/path.md)
     * - Markdown images: ![alt](/path) -> ![alt](https://example.com/path) (no .md)
     * - File links (.pdf, .jpg, etc.) -> absolute URL without .md
     */
    private function convertRelativeLinksToAbsolute(string $markdown, string $baseUrl): string
    {
        if ($baseUrl === '') {
            return $markdown;
        }

        // Convert relative image sources first (no .md suffix for images)
        // Match ![alt](/path) but not ![alt](http
        $markdown = preg_replace_callback(
            '/(!\[[^\]]*\]\()(\/)([^)]*\))/',
            static fn(array $matches): string => $matches[1] . $baseUrl . '/' . $matches[3],
            $markdown,
        ) ?? $markdown;

        // Convert relative links: [text](/path) -> [text](https://example.com/path.md)
        // Match [text](/ but not [text](http or [text](https or [text](// or images ![
        $markdown = preg_replace_callback(
            '/(?<!!)\[([^\]]*)\]\(\/([^)]*)\)/',
            static function (array $matches) use ($baseUrl): string {
                $text = $matches[1];
                $path = $matches[2];

                // Build absolute URL
                $absoluteUrl = $baseUrl . '/' . $path;

                // Add .md suffix for internal page links (not for files with extensions)
                if (!self::isFileLink($path)) {
                    $absoluteUrl = self::appendMdSuffix($absoluteUrl);
                }

                return '[' . $text . '](' . $absoluteUrl . ')';
            },
            $markdown,
        ) ?? $markdown;

        return $markdown;
    }

    /**
     * Check if a path points to a file (has a file extension).
     */
    private static function isFileLink(string $path): bool
    {
        // Remove query string and fragment for checking
        $cleanPath = preg_replace('/[?#].*$/', '', $path) ?? $path;

        // Check for common file extensions (not page URLs)
        $fileExtensions = [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'zip', 'tar', 'gz', 'rar',
            'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico',
            'mp3', 'mp4', 'wav', 'avi', 'mov', 'webm',
            'css', 'js', 'json', 'xml', 'txt', 'csv',
        ];

        $extension = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));

        return \in_array($extension, $fileExtensions, true);
    }

    /**
     * Append .md suffix to a URL for internal page links.
     */
    private static function appendMdSuffix(string $url): string
    {
        // Parse URL to handle query strings and fragments
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '/';

        // Remove trailing slash and append .md
        $path = $path === '/' ? '/index.html.md' : rtrim($path, '/') . '.md';

        // Reconstruct URL
        $result = '';
        if (isset($parsedUrl['scheme'])) {
            $result .= $parsedUrl['scheme'] . '://';
        }
        if (isset($parsedUrl['host'])) {
            $result .= $parsedUrl['host'];
        }
        if (isset($parsedUrl['port'])) {
            $result .= ':' . $parsedUrl['port'];
        }
        $result .= $path;
        if (isset($parsedUrl['query'])) {
            $result .= '?' . $parsedUrl['query'];
        }
        if (isset($parsedUrl['fragment'])) {
            $result .= '#' . $parsedUrl['fragment'];
        }

        return $result;
    }
}
