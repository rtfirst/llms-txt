<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Converter;

/**
 * Converter for HTML content elements.
 */
final class HtmlConverter extends AbstractContentConverter
{
    public function supports(string $cType): bool
    {
        return $cType === 'html';
    }

    public function convert(array $record, string $baseUrl): string
    {
        $parts = [];

        // Header
        $header = $this->getHeaderMarkdown($record);
        if ($header !== '') {
            $parts[] = $header;
        }

        // HTML content from bodytext
        $bodytext = trim((string)($record['bodytext'] ?? ''));
        if ($bodytext !== '') {
            $markdown = $this->htmlToMarkdown($bodytext);
            if ($markdown !== '') {
                $parts[] = $markdown;
            }
        }

        return implode("\n\n", $parts);
    }
}
