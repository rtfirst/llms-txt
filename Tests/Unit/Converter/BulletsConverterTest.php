<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Unit\Converter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RTfirst\LlmsTxt\Converter\BulletsConverter;
use TYPO3\CMS\Core\Resource\FileRepository;

final class BulletsConverterTest extends TestCase
{
    private BulletsConverter $converter;

    protected function setUp(): void
    {
        $fileRepository = $this->createMock(FileRepository::class);
        $this->converter = new BulletsConverter($fileRepository);
    }

    #[Test]
    public function supportsBulletsCType(): void
    {
        self::assertTrue($this->converter->supports('bullets'));
    }

    #[Test]
    public function doesNotSupportOtherCTypes(): void
    {
        self::assertFalse($this->converter->supports('text'));
        self::assertFalse($this->converter->supports('table'));
    }

    #[Test]
    public function convertsUnorderedList(): void
    {
        $record = [
            'CType' => 'bullets',
            'header' => 'Features',
            'header_layout' => 2,
            'bullets_type' => 0,
            'bodytext' => "Feature 1\nFeature 2\nFeature 3",
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertStringContainsString('## Features', $result);
        self::assertStringContainsString('- Feature 1', $result);
        self::assertStringContainsString('- Feature 2', $result);
        self::assertStringContainsString('- Feature 3', $result);
    }

    #[Test]
    public function convertsOrderedList(): void
    {
        $record = [
            'CType' => 'bullets',
            'bullets_type' => 1,
            'bodytext' => "Step 1\nStep 2\nStep 3",
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertStringContainsString('1. Step 1', $result);
        self::assertStringContainsString('2. Step 2', $result);
        self::assertStringContainsString('3. Step 3', $result);
    }

    #[Test]
    public function handlesEmptyList(): void
    {
        $record = [
            'CType' => 'bullets',
            'header' => 'Empty List',
            'header_layout' => 2,
            'bodytext' => '',
        ];

        $result = $this->converter->convert($record, 'https://example.com');

        self::assertSame('## Empty List', $result);
    }
}
