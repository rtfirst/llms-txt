<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Converter;

/**
 * Converter for image content elements.
 */
final class ImageConverter extends AbstractContentConverter
{
    public function supports(string $cType): bool
    {
        return $cType === 'image';
    }

    public function convert(array $record, string $baseUrl): string
    {
        $parts = [];

        // Header
        $header = $this->getHeaderMarkdown($record);
        if ($header !== '') {
            $parts[] = $header;
        }

        // Images
        $uid = (int)($record['uid'] ?? 0);
        if ($uid > 0) {
            $fileReferences = $this->getFileReferences($uid, 'image');
            $imagesMarkdown = $this->getImagesMarkdown($fileReferences, $baseUrl);
            if ($imagesMarkdown !== '') {
                $parts[] = $imagesMarkdown;
            }
        }

        return implode("\n\n", $parts);
    }
}
