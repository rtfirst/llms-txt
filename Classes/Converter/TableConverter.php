<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Converter;

/**
 * Converter for table content elements.
 */
final class TableConverter extends AbstractContentConverter
{
    public function supports(string $cType): bool
    {
        return $cType === 'table';
    }

    public function convert(array $record, string $baseUrl): string
    {
        $parts = [];

        // Header
        $header = $this->getHeaderMarkdown($record);
        if ($header !== '') {
            $parts[] = $header;
        }

        // Table content from bodytext
        $bodytext = trim((string)($record['bodytext'] ?? ''));
        if ($bodytext !== '') {
            $tableMarkdown = $this->convertTableData($bodytext);
            if ($tableMarkdown !== '') {
                $parts[] = $tableMarkdown;
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Convert TYPO3 table data (line/pipe separated) to Markdown table.
     */
    private function convertTableData(string $bodytext): string
    {
        $rows = explode("\n", $bodytext);
        $rows = array_filter($rows, static fn(string $row): bool => trim($row) !== '');

        if ($rows === []) {
            return '';
        }

        $tableRows = [];
        $maxColumns = 0;

        // Parse rows and find max column count
        foreach ($rows as $row) {
            $cells = explode('|', $row);
            $cells = array_map('trim', $cells);
            $tableRows[] = $cells;
            $maxColumns = max($maxColumns, \count($cells));
        }

        // Build Markdown table
        $lines = [];

        // First row (header) - always exists since $rows was non-empty after filter
        $firstRow = array_shift($tableRows);

        // Pad first row to max columns
        while (\count($firstRow) < $maxColumns) {
            $firstRow[] = '';
        }

        $lines[] = '| ' . implode(' | ', $firstRow) . ' |';

        // Separator row
        $separators = array_fill(0, $maxColumns, '---');
        $lines[] = '| ' . implode(' | ', $separators) . ' |';

        // Data rows
        foreach ($tableRows as $row) {
            // Pad row to max columns
            while (\count($row) < $maxColumns) {
                $row[] = '';
            }
            $lines[] = '| ' . implode(' | ', $row) . ' |';
        }

        return implode("\n", $lines);
    }
}
