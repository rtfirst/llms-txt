<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RTfirst\LlmsTxt\Service\PageTreeService;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class PageTreeServiceTest extends TestCase
{
    private PageTreeService $service;

    protected function setUp(): void
    {
        $this->service = new PageTreeService($this->createStub(ConnectionPool::class), new NullLogger());
    }

    /**
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function pageProvider(): array
    {
        return [
            'standard page' => [['doktype' => 1], false],
            'page type of an extension' => [['doktype' => 116], false],
            'link' => [['doktype' => 3], true],
            'shortcut' => [['doktype' => 4], true],
            // Rendered with the content of the mounted page at its own URL
            'mount point showing the mounted page' => [['doktype' => 7, 'mount_pid_ol' => 1], false],
            'mount point showing its own content' => [['doktype' => 7, 'mount_pid_ol' => 0], false],
        ];
    }

    /**
     * @param array<string, mixed> $page
     */
    #[Test]
    #[DataProvider('pageProvider')]
    public function isLinkOrShortcutDetectsLinkAndShortcutPages(array $page, bool $expected): void
    {
        self::assertSame($expected, $this->service->isLinkOrShortcut($page));
    }
}
