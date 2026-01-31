<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RTfirst\LlmsTxt\Converter\ContentConverterInterface;
use RTfirst\LlmsTxt\Service\MarkdownConverterService;

final class MarkdownConverterServiceTest extends TestCase
{
    #[Test]
    public function convertsRecordUsingMatchingConverter(): void
    {
        $converter = $this->createMock(ContentConverterInterface::class);
        $converter->method('supports')->willReturnCallback(
            static fn(string $cType): bool => $cType === 'text',
        );
        $converter->method('convert')->willReturn('Converted text');

        $service = new MarkdownConverterService([$converter]);

        $record = ['CType' => 'text', 'bodytext' => 'Test'];
        $result = $service->convert($record, 'https://example.com');

        self::assertSame('Converted text', $result);
    }

    #[Test]
    public function returnsEmptyForUnsupportedType(): void
    {
        $converter = $this->createMock(ContentConverterInterface::class);
        $converter->method('supports')->willReturn(false);

        $service = new MarkdownConverterService([$converter]);

        $record = ['CType' => 'unknown'];
        $result = $service->convert($record, 'https://example.com');

        self::assertSame('', $result);
    }

    #[Test]
    public function convertsAllRecords(): void
    {
        $converter = $this->createMock(ContentConverterInterface::class);
        $converter->method('supports')->willReturn(true);
        $converter->method('convert')->willReturnOnConsecutiveCalls(
            'First content',
            'Second content',
            'Third content',
        );

        $service = new MarkdownConverterService([$converter]);

        $records = [
            ['CType' => 'text', 'bodytext' => 'First'],
            ['CType' => 'text', 'bodytext' => 'Second'],
            ['CType' => 'text', 'bodytext' => 'Third'],
        ];

        $result = $service->convertAll($records, 'https://example.com');

        self::assertStringContainsString('First content', $result);
        self::assertStringContainsString('Second content', $result);
        self::assertStringContainsString('Third content', $result);
    }

    #[Test]
    public function skipsEmptyConversions(): void
    {
        $converter = $this->createMock(ContentConverterInterface::class);
        $converter->method('supports')->willReturn(true);
        $converter->method('convert')->willReturnOnConsecutiveCalls(
            'First',
            '',
            'Third',
        );

        $service = new MarkdownConverterService([$converter]);

        $records = [
            ['CType' => 'text'],
            ['CType' => 'text'],
            ['CType' => 'text'],
        ];

        $result = $service->convertAll($records, 'https://example.com');

        self::assertSame("First\n\nThird", $result);
    }

    #[Test]
    public function usesFirstMatchingConverter(): void
    {
        $converter1 = $this->createMock(ContentConverterInterface::class);
        $converter1->method('supports')->willReturn(true);
        $converter1->method('convert')->willReturn('From first');

        $converter2 = $this->createMock(ContentConverterInterface::class);
        $converter2->method('supports')->willReturn(true);
        $converter2->method('convert')->willReturn('From second');

        $service = new MarkdownConverterService([$converter1, $converter2]);

        $record = ['CType' => 'text'];
        $result = $service->convert($record, 'https://example.com');

        self::assertSame('From first', $result);
    }
}
