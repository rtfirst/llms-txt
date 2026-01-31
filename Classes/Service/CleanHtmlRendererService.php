<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Service;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Service to render clean semantic HTML without navigation, CSS, and JS.
 */
final class CleanHtmlRendererService
{
    /**
     * Render page content as clean semantic HTML.
     */
    public function render(
        string $originalHtml,
        int $pageId,
        ?SiteLanguage $language,
        string $baseUrl,
    ): string {
        // Extract page metadata from original HTML
        $title = $this->extractTitle($originalHtml);
        $description = $this->extractMetaDescription($originalHtml);
        $canonical = $this->extractCanonical($originalHtml);

        // Extract main content from original HTML
        $mainContent = $this->extractMainContent($originalHtml);

        // Build clean HTML document
        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="' . ($language?->getLocale()->getLanguageCode() ?? 'de') . '">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '  <meta charset="UTF-8">' . "\n";
        $html .= '  <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $html .= '  <title>' . htmlspecialchars($title) . '</title>' . "\n";

        if ($description !== '') {
            $html .= '  <meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
        }

        if ($canonical !== '') {
            $html .= '  <link rel="canonical" href="' . htmlspecialchars($canonical) . '">' . "\n";
        }

        $html .= '  <meta name="robots" content="noindex, nofollow">' . "\n";
        $html .= '  <meta name="generator" content="TYPO3 LLMs.txt Extension">' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '<article>' . "\n";
        $html .= '  <header>' . "\n";
        $html .= '    <h1>' . htmlspecialchars($title) . '</h1>' . "\n";
        $html .= '  </header>' . "\n";
        $html .= '  <main>' . "\n";
        $html .= $mainContent;
        $html .= '  </main>' . "\n";
        $html .= '</article>' . "\n";
        $html .= '</body>' . "\n";

        return $html . '</html>';
    }

    /**
     * Extract title from HTML.
     */
    private function extractTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
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
     * Extract main content from HTML, removing navigation, scripts, styles.
     */
    private function extractMainContent(string $html): string
    {
        // Try to find main content area
        $content = $html;

        // Remove script and style tags
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content) ?? $content;
        $content = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $content) ?? $content;

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

        // Remove inline styles and event handlers
        $content = preg_replace('/\s+style=["\'][^"\']*["\']/', '', $content) ?? $content;
        $content = preg_replace('/\s+on\w+=["\'][^"\']*["\']/', '', $content) ?? $content;

        // Remove class and id attributes (optional - keeps semantic structure)
        // $content = preg_replace('/\s+(?:class|id)=["\'][^"\']*["\']/', '', $content) ?? $content;

        // Remove empty tags
        $content = preg_replace('/<(\w+)\b[^>]*>\s*<\/\1>/', '', $content) ?? $content;

        // Clean up whitespace
        $content = preg_replace('/\n\s*\n/', "\n\n", $content) ?? $content;
        $content = trim($content);

        return $content !== '' ? $content : '<p>No content available.</p>';
    }
}
