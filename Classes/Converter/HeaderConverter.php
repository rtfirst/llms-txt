<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Converter;

/**
 * Converter for header content elements.
 */
final class HeaderConverter extends AbstractContentConverter
{
    public function supports(string $cType): bool
    {
        return $cType === 'header';
    }

    public function convert(array $record, string $baseUrl): string
    {
        $parts = [];

        $header = $this->getHeaderMarkdown($record);
        if ($header !== '') {
            $parts[] = $header;
        }

        // Include subheader if present
        $subheader = trim((string)($record['subheader'] ?? ''));
        if ($subheader !== '') {
            $parts[] = '*' . $subheader . '*';
        }

        return implode("\n\n", $parts);
    }
}
