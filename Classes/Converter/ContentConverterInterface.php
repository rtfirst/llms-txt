<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Converter;

/**
 * Interface for content element to Markdown converters.
 */
interface ContentConverterInterface
{
    /**
     * Check if this converter supports the given content type.
     *
     * @param string $cType The content element type (CType)
     */
    public function supports(string $cType): bool;

    /**
     * Convert a content element record to Markdown.
     *
     * @param array<string, mixed> $record The tt_content record
     * @param string $baseUrl The base URL for generating absolute links
     * @return string The Markdown representation
     */
    public function convert(array $record, string $baseUrl): string;
}
