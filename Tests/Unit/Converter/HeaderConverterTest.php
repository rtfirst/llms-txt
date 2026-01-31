<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Unit\Converter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RTfirst\LlmsTxt\Converter\HeaderConverter;
use TYPO3\CMS\Core\Resource\FileRepository;

final class HeaderConverterTest extends TestCase
{
    private HeaderConverter $converter;

    protected function setUp(): void
    {
        $fileRepository = $this->createMock(FileRepository::class);
        $this->converter = new HeaderConverter($fileRepository);
    }

    #[Test]
    public function supportsHeaderCType(): void
    {
        self::assertTrue($this->converter->supports('header'));
    }

    #[Test]
    public function doesNotSupportOtherCTypes(): void
    {
        self::assertFalse($this->converter->supports('text'));
        self::assertFalse($this->converter->supports('image'));
    }

    #[Test]
    public function convertsHeaderToMarkdown(): void
    {
        $record = [
            'header' => 'Test Header',
            'header_layout' => 2,
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertSame('## Test Header', $result);
    }

    #[Test]
    public function convertsHeaderWithSubheader(): void
    {
        $record = [
            'header' => 'Main Header',
            'header_layout' => 1,
            'subheader' => 'Subheader Text',
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertStringContainsString('# Main Header', $result);
        self::assertStringContainsString('*Subheader Text*', $result);
    }

    #[Test]
    public function returnsEmptyForHiddenHeader(): void
    {
        $record = [
            'header' => 'Hidden Header',
            'header_layout' => 100,
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertSame('', $result);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function headerLayoutProvider(): array
    {
        return [
            'layout 0 default' => [0, '##'],
            'layout 1 h1' => [1, '#'],
            'layout 2 h2' => [2, '##'],
            'layout 3 h3' => [3, '###'],
            'layout 4 h4' => [4, '####'],
            'layout 5 h5' => [5, '#####'],
        ];
    }

    #[Test]
    #[DataProvider('headerLayoutProvider')]
    public function convertsHeaderLayoutCorrectly(int $layout, string $expectedPrefix): void
    {
        $record = [
            'header' => 'Test',
            'header_layout' => $layout,
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertStringStartsWith($expectedPrefix . ' Test', $result);
    }
}
