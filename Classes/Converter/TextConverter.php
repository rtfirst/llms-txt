<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Converter;

/**
 * Converter for text, textpic, and textmedia content elements.
 */
final class TextConverter extends AbstractContentConverter
{
    private const SUPPORTED_TYPES = ['text', 'textpic', 'textmedia'];

    public function supports(string $cType): bool
    {
        return \in_array($cType, self::SUPPORTED_TYPES, true);
    }

    public function convert(array $record, string $baseUrl): string
    {
        $parts = [];

        // Header
        $header = $this->getHeaderMarkdown($record);
        if ($header !== '') {
            $parts[] = $header;
        }

        // Body text (RTE content)
        $bodytext = trim((string)($record['bodytext'] ?? ''));
        if ($bodytext !== '') {
            $markdown = $this->htmlToMarkdown($bodytext);
            if ($markdown !== '') {
                $parts[] = $markdown;
            }
        }

        // Images (for textpic and textmedia)
        $cType = (string)($record['CType'] ?? '');
        if (\in_array($cType, ['textpic', 'textmedia'], true)) {
            $uid = (int)($record['uid'] ?? 0);
            if ($uid > 0) {
                // textmedia uses 'assets', textpic uses 'image'
                $fieldName = $cType === 'textmedia' ? 'assets' : 'image';
                $fileReferences = $this->getFileReferences($uid, $fieldName);
                $imagesMarkdown = $this->getImagesMarkdown($fileReferences, $baseUrl);
                if ($imagesMarkdown !== '') {
                    $parts[] = $imagesMarkdown;
                }
            }
        }

        return implode("\n\n", $parts);
    }
}
