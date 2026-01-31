<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Converter;

/**
 * Default/fallback converter for unsupported content types.
 * Extracts header and bodytext if available.
 */
final class DefaultConverter extends AbstractContentConverter
{
    public function supports(string $cType): bool
    {
        // This converter acts as a fallback and supports all types
        return true;
    }

    public function convert(array $record, string $baseUrl): string
    {
        $parts = [];

        // Header
        $header = $this->getHeaderMarkdown($record);
        if ($header !== '') {
            $parts[] = $header;
        }

        // Try to extract bodytext if present
        $bodytext = trim((string)($record['bodytext'] ?? ''));
        if ($bodytext !== '') {
            // Try to convert as HTML (will work for plain text too)
            $markdown = $this->htmlToMarkdown($bodytext);
            if ($markdown !== '') {
                $parts[] = $markdown;
            }
        }

        // If no content extracted, add a placeholder with the CType for debugging
        if ($parts === []) {
            $cType = (string)($record['CType'] ?? 'unknown');
            // Only add placeholder in development context - skip for production
            // return '*[Content element: ' . $cType . ']*';
            return '';
        }

        return implode("\n\n", $parts);
    }
}
