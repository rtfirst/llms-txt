<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Converter;

/**
 * Converter for bullets (list) content elements.
 */
final class BulletsConverter extends AbstractContentConverter
{
    public function supports(string $cType): bool
    {
        return $cType === 'bullets';
    }

    public function convert(array $record, string $baseUrl): string
    {
        $parts = [];

        // Header
        $header = $this->getHeaderMarkdown($record);
        if ($header !== '') {
            $parts[] = $header;
        }

        // List items from bodytext
        $bodytext = trim((string)($record['bodytext'] ?? ''));
        if ($bodytext !== '') {
            $listMarkdown = $this->convertBulletList($bodytext, (int)($record['bullets_type'] ?? 0));
            if ($listMarkdown !== '') {
                $parts[] = $listMarkdown;
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Convert TYPO3 bullet list data to Markdown list.
     *
     * @param int $bulletsType 0 = unordered, 1 = ordered, 2 = definition list
     */
    private function convertBulletList(string $bodytext, int $bulletsType): string
    {
        $items = explode("\n", $bodytext);
        $items = array_filter($items, static fn(string $item): bool => trim($item) !== '');

        if ($items === []) {
            return '';
        }

        $lines = [];
        $counter = 1;

        foreach ($items as $item) {
            $item = trim($item);
            // Strip HTML if present
            $item = $this->htmlToMarkdown($item);

            if ($bulletsType === 1) {
                // Ordered list
                $lines[] = $counter . '. ' . $item;
                $counter++;
            } elseif ($bulletsType === 2) {
                // Definition list - items contain term|definition
                $parts = explode('|', $item, 2);
                $term = trim($parts[0]);
                $definition = isset($parts[1]) ? trim($parts[1]) : '';
                $lines[] = '**' . $term . '**';
                if ($definition !== '') {
                    $lines[] = ': ' . $definition;
                }
                $lines[] = '';
            } else {
                // Unordered list (default)
                $lines[] = '- ' . $item;
            }
        }

        return implode("\n", $lines);
    }
}
