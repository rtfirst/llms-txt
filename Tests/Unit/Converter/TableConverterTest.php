<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Unit\Converter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RTfirst\LlmsTxt\Converter\TableConverter;
use TYPO3\CMS\Core\Resource\FileRepository;

final class TableConverterTest extends TestCase
{
    private TableConverter $converter;

    protected function setUp(): void
    {
        $fileRepository = $this->createMock(FileRepository::class);
        $this->converter = new TableConverter($fileRepository);
    }

    #[Test]
    public function supportsTableCType(): void
    {
        self::assertTrue($this->converter->supports('table'));
    }

    #[Test]
    public function doesNotSupportOtherCTypes(): void
    {
        self::assertFalse($this->converter->supports('text'));
        self::assertFalse($this->converter->supports('header'));
    }

    #[Test]
    public function convertsTableToMarkdown(): void
    {
        $record = [
            'CType' => 'table',
            'header' => 'Price List',
            'header_layout' => 2,
            'bodytext' => "Product|Price\nApple|1.00\nBanana|0.50",
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertStringContainsString('## Price List', $result);
        self::assertStringContainsString('| Product | Price |', $result);
        self::assertStringContainsString('| --- | --- |', $result);
        self::assertStringContainsString('| Apple | 1.00 |', $result);
        self::assertStringContainsString('| Banana | 0.50 |', $result);
    }

    #[Test]
    public function handlesEmptyTable(): void
    {
        $record = [
            'CType' => 'table',
            'header' => 'Empty Table',
            'header_layout' => 2,
            'bodytext' => '',
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertSame('## Empty Table', $result);
    }

    #[Test]
    public function handlesSingleRowTable(): void
    {
        $record = [
            'CType' => 'table',
            'bodytext' => 'Col1|Col2|Col3',
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertStringContainsString('| Col1 | Col2 | Col3 |', $result);
        self::assertStringContainsString('| --- | --- | --- |', $result);
    }
}
