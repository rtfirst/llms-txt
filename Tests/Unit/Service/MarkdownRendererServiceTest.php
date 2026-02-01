<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RTfirst\LlmsTxt\Service\MarkdownRendererService;

final class MarkdownRendererServiceTest extends TestCase
{
    private MarkdownRendererService $service;

    protected function setUp(): void
    {
        $this->service = new MarkdownRendererService();
    }

    #[Test]
    public function renderReturnsMarkdownWithFrontmatter(): void
    {
        $html = '<html><head><title>Test Page</title></head><body><main><p>Content</p></main></body></html>';

        $result = $this->service->render($html, 1, null, 'https://example.com');

        self::assertStringContainsString('---', $result);
        self::assertStringContainsString('title: "Test Page"', $result);
        self::assertStringContainsString('format: markdown', $result);
    }

    #[Test]
    public function renderExtractsMetaDescription(): void
    {
        $html = '<html><head><title>Test</title><meta name="description" content="Test description"></head><body></body></html>';

        $result = $this->service->render($html, 1, null, 'https://example.com');

        self::assertStringContainsString('description: "Test description"', $result);
    }

    #[Test]
    public function renderExtractsCanonicalUrl(): void
    {
        $html = '<html><head><title>Test</title><link rel="canonical" href="https://example.com/page"></head><body></body></html>';

        $result = $this->service->render($html, 1, null, 'https://example.com');

        self::assertStringContainsString('canonical: "https://example.com/page"', $result);
    }

    #[Test]
    public function renderConvertsRelativeLinksToAbsolute(): void
    {
        $html = '<html><head><title>Test</title></head><body><main><a href="/about">About</a></main></body></html>';

        $result = $this->service->render($html, 1, null, 'https://example.com');

        self::assertStringContainsString('[About](https://example.com/about)', $result);
        self::assertStringNotContainsString('[About](/about)', $result);
    }

    #[Test]
    public function renderConvertsRelativeImageSourcesToAbsolute(): void
    {
        $html = '<html><head><title>Test</title></head><body><main><img src="/images/test.jpg" alt="Test"></main></body></html>';

        $result = $this->service->render($html, 1, null, 'https://example.com');

        self::assertStringContainsString('https://example.com/images/test.jpg', $result);
    }

    #[Test]
    public function renderPreservesAbsoluteLinks(): void
    {
        $html = '<html><head><title>Test</title></head><body><main><a href="https://other.com/page">External</a></main></body></html>';

        $result = $this->service->render($html, 1, null, 'https://example.com');

        self::assertStringContainsString('https://other.com/page', $result);
    }

    #[Test]
    public function renderHandlesEmptyBaseUrl(): void
    {
        $html = '<html><head><title>Test</title></head><body><main><a href="/about">About</a></main></body></html>';

        $result = $this->service->render($html, 1, null, '');

        // Links should remain relative when base URL is empty
        self::assertStringContainsString('/about', $result);
    }

    #[Test]
    #[DataProvider('relativeLinksProvider')]
    public function renderConvertsVariousRelativeLinkFormats(string $inputLink, string $expectedLink): void
    {
        $html = '<html><head><title>Test</title></head><body><main><a href="' . $inputLink . '">Link</a></main></body></html>';

        $result = $this->service->render($html, 1, null, 'https://example.com');

        self::assertStringContainsString($expectedLink, $result);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function relativeLinksProvider(): array
    {
        return [
            'simple path' => ['/about', 'https://example.com/about'],
            'nested path' => ['/products/shoes', 'https://example.com/products/shoes'],
            'path with extension' => ['/page.html', 'https://example.com/page.html'],
            'root path' => ['/', 'https://example.com/'],
        ];
    }

    #[Test]
    public function renderRemovesScriptTags(): void
    {
        $html = '<html><head><title>Test</title></head><body><main><p>Content</p><script>alert("test")</script></main></body></html>';

        $result = $this->service->render($html, 1, null, 'https://example.com');

        self::assertStringNotContainsString('alert', $result);
        self::assertStringNotContainsString('<script>', $result);
    }

    #[Test]
    public function renderRemovesNavigation(): void
    {
        $html = '<html><head><title>Test</title></head><body><nav>Menu</nav><main><p>Content</p></main></body></html>';

        $result = $this->service->render($html, 1, null, 'https://example.com');

        self::assertStringContainsString('Content', $result);
        self::assertStringNotContainsString('Menu', $result);
    }
}
