<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Service;

use RTfirst\LlmsTxt\Converter\ContentConverterInterface;

/**
 * Service that orchestrates content element to Markdown conversion.
 */
final class MarkdownConverterService
{
    /**
     * @var ContentConverterInterface[]
     */
    private array $converters = [];

    /**
     * @param iterable<ContentConverterInterface> $converters
     */
    public function __construct(iterable $converters)
    {
        foreach ($converters as $converter) {
            $this->converters[] = $converter;
        }
    }

    /**
     * Convert a content element record to Markdown.
     *
     * @param array<string, mixed> $record The tt_content record
     * @param string $baseUrl The base URL for generating absolute links
     */
    public function convert(array $record, string $baseUrl): string
    {
        $cType = (string)($record['CType'] ?? '');

        foreach ($this->converters as $converter) {
            if ($converter->supports($cType)) {
                return $converter->convert($record, $baseUrl);
            }
        }

        // No converter found - return empty string
        return '';
    }

    /**
     * Convert multiple content elements to Markdown.
     *
     * @param array<int, array<string, mixed>> $records
     */
    public function convertAll(array $records, string $baseUrl): string
    {
        $parts = [];

        foreach ($records as $record) {
            $markdown = $this->convert($record, $baseUrl);
            if ($markdown !== '') {
                $parts[] = $markdown;
            }
        }

        return implode("\n\n", $parts);
    }
}
