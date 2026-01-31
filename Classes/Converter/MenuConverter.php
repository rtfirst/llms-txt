<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Converter;

/**
 * Converter for menu content elements.
 * Skips menu elements as navigation is already included in the page structure.
 */
final class MenuConverter extends AbstractContentConverter
{
    private const MENU_TYPES = [
        'menu_abstract',
        'menu_categorized_content',
        'menu_categorized_pages',
        'menu_pages',
        'menu_recently_updated',
        'menu_related_pages',
        'menu_section',
        'menu_section_pages',
        'menu_sitemap',
        'menu_sitemap_pages',
        'menu_subpages',
    ];

    public function supports(string $cType): bool
    {
        return \in_array($cType, self::MENU_TYPES, true);
    }

    public function convert(array $record, string $baseUrl): string
    {
        // Menu elements are intentionally skipped as the page structure
        // is already represented in the llms.txt navigation section.
        // Only output header if present and explicitly wanted.
        $header = $this->getHeaderMarkdown($record);
        if ($header !== '') {
            return $header . "\n\n*[Navigation menu]*";
        }

        return '';
    }
}
