<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Converter;

use Exception;
use League\HTMLToMarkdown\HtmlConverter;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;

/**
 * Abstract base class for content converters with common helper methods.
 */
abstract class AbstractContentConverter implements ContentConverterInterface
{
    protected ?HtmlConverter $htmlConverter = null;

    public function __construct(
        protected readonly FileRepository $fileRepository,
    ) {}

    /**
     * Convert HTML to Markdown.
     */
    protected function htmlToMarkdown(string $html): string
    {
        if ($html === '') {
            return '';
        }

        if (!$this->htmlConverter instanceof HtmlConverter) {
            $this->htmlConverter = new HtmlConverter([
                'strip_tags' => true,
                'remove_nodes' => 'script style',
                'hard_break' => true,
            ]);
        }

        return trim($this->htmlConverter->convert($html));
    }

    /**
     * Get header markdown based on header type.
     *
     * @param array<string, mixed> $record
     */
    protected function getHeaderMarkdown(array $record): string
    {
        $header = trim((string)($record['header'] ?? ''));
        if ($header === '') {
            return '';
        }

        $headerLayout = (int)($record['header_layout'] ?? 0);

        // header_layout: 0 = default (h2), 1 = h1, 2 = h2, 3 = h3, 4 = h4, 5 = h5, 100 = hidden
        if ($headerLayout === 100) {
            return '';
        }

        $level = match ($headerLayout) {
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            default => 2,
        };

        $prefix = str_repeat('#', $level);

        return $prefix . ' ' . $header;
    }

    /**
     * Get file references for a content element.
     *
     * @return FileReference[]
     */
    protected function getFileReferences(int $uid, string $fieldName = 'image'): array
    {
        try {
            return $this->fileRepository->findByRelation('tt_content', $fieldName, $uid);
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Generate Markdown for images.
     *
     * @param FileReference[] $fileReferences
     */
    protected function getImagesMarkdown(array $fileReferences, string $baseUrl): string
    {
        $lines = [];

        foreach ($fileReferences as $fileReference) {
            $alt = $fileReference->getAlternative() ?: $fileReference->getTitle() ?: $fileReference->getName();
            $publicUrl = $fileReference->getPublicUrl();

            if ($publicUrl !== null) {
                // Make URL absolute if it's relative
                if (!str_starts_with($publicUrl, 'http')) {
                    $publicUrl = rtrim($baseUrl, '/') . '/' . ltrim($publicUrl, '/');
                }
                $lines[] = '![' . $this->escapeMarkdown($alt) . '](' . $publicUrl . ')';
            }
        }

        return implode("\n\n", $lines);
    }

    /**
     * Escape special Markdown characters in text.
     */
    protected function escapeMarkdown(string $text): string
    {
        // Only escape characters that would break Markdown syntax in alt text or similar
        return str_replace(['[', ']', '(', ')'], ['\[', '\]', '\(', '\)'], $text);
    }

    /**
     * Clean and normalize whitespace in text.
     */
    protected function normalizeWhitespace(string $text): string
    {
        // Replace multiple newlines with double newline (paragraph break)
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        // Trim each line
        $lines = array_map(trim(...), explode("\n", $text));

        return implode("\n", $lines);
    }
}
