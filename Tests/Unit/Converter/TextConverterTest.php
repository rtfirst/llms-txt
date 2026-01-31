<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Unit\Converter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RTfirst\LlmsTxt\Converter\TextConverter;
use TYPO3\CMS\Core\Resource\FileRepository;

final class TextConverterTest extends TestCase
{
    private TextConverter $converter;

    protected function setUp(): void
    {
        $fileRepository = $this->createMock(FileRepository::class);
        $this->converter = new TextConverter($fileRepository);
    }

    #[Test]
    public function supportsTextCType(): void
    {
        self::assertTrue($this->converter->supports('text'));
    }

    #[Test]
    public function supportsTextpicCType(): void
    {
        self::assertTrue($this->converter->supports('textpic'));
    }

    #[Test]
    public function supportsTextmediaCType(): void
    {
        self::assertTrue($this->converter->supports('textmedia'));
    }

    #[Test]
    public function doesNotSupportOtherCTypes(): void
    {
        self::assertFalse($this->converter->supports('header'));
        self::assertFalse($this->converter->supports('image'));
    }

    #[Test]
    public function convertsTextToMarkdown(): void
    {
        $record = [
            'CType' => 'text',
            'header' => 'My Header',
            'header_layout' => 2,
            'bodytext' => '<p>This is <strong>bold</strong> text.</p>',
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertStringContainsString('## My Header', $result);
        self::assertStringContainsString('**bold**', $result);
    }

    #[Test]
    public function convertsPlainTextBodytext(): void
    {
        $record = [
            'CType' => 'text',
            'bodytext' => 'Plain text content',
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertStringContainsString('Plain text content', $result);
    }

    #[Test]
    public function handlesEmptyBodytext(): void
    {
        $record = [
            'CType' => 'text',
            'header' => 'Only Header',
            'header_layout' => 3,
            'bodytext' => '',
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertSame('### Only Header', $result);
    }
}
