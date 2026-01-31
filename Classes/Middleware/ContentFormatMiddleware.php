<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RTfirst\LlmsTxt\Service\MarkdownRendererService;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Middleware that provides LLM-optimized Markdown content format.
 *
 * Supports (spec-compliant with https://llmstxt.org/):
 * - /page.md - Markdown with YAML frontmatter (via URL suffix)
 *
 * Uses TYPO3 caching to reduce database load.
 */
final readonly class ContentFormatMiddleware implements MiddlewareInterface
{
    use ApiKeyAuthenticationTrait;

    private const FORMAT_MARKDOWN = 'md';

    public function __construct(
        private MarkdownRendererService $markdownRenderer,
        private FrontendInterface $cache,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check for format from URL suffix (.md) or query parameter (?format=)
        $format = $this->detectFormat($request);

        // Only handle our special formats
        if ($format === null) {
            return $handler->handle($request);
        }

        // Check API key protection (via ApiKeyAuthenticationTrait)
        $authResponse = $this->checkApiKeyAuth($request);
        if ($authResponse !== null) {
            return $authResponse;
        }

        // Get page information from request (available before handler)
        $pageId = $this->getPageId($request);
        $language = $this->getLanguage($request);

        if ($pageId === null) {
            return $handler->handle($request);
        }

        // Generate cache key
        $languageId = $this->extractLanguageId($language);
        $cacheKey = $this->generateCacheKey($pageId, $languageId, $format);

        // Try to get from cache
        if ($this->cache->has($cacheKey)) {
            $cachedData = $this->cache->get($cacheKey);
            if (\is_array($cachedData) && isset($cachedData['content'], $cachedData['contentType'])) {
                $newResponse = new Response();
                $newResponse->getBody()->write($cachedData['content']);

                return $newResponse
                    ->withHeader('Content-Type', $cachedData['contentType'])
                    ->withHeader('X-Content-Format', $format)
                    ->withHeader('X-Robots-Tag', 'noindex')
                    ->withHeader('X-Cache', 'HIT');
            }
        }

        // Get the normal response first
        $response = $handler->handle($request);

        // Only process successful HTML responses
        $contentType = $response->getHeaderLine('Content-Type');
        if ($response->getStatusCode() !== 200 || !str_contains($contentType, 'text/html')) {
            return $response;
        }

        $baseUrl = $this->getBaseUrl($request);

        // Get original HTML content
        $originalHtml = (string)$response->getBody();

        // Render as Markdown
        $content = $this->markdownRenderer->render($originalHtml, $pageId, $language, $baseUrl);
        $contentType = 'text/plain; charset=utf-8';

        // Store in cache with page tag for targeted invalidation
        $this->cache->set(
            $cacheKey,
            ['content' => $content, 'contentType' => $contentType],
            ['pageId_' . $pageId],
        );

        // Create new response with formatted content
        $newResponse = new Response();
        $newResponse->getBody()->write($content);

        return $newResponse
            ->withHeader('Content-Type', $contentType)
            ->withHeader('X-Content-Format', $format)
            ->withHeader('X-Robots-Tag', 'noindex')
            ->withHeader('X-Cache', 'MISS');
    }

    /**
     * Detect the requested Markdown format from URL suffix attribute.
     *
     * Only supports .md URL suffix (spec-compliant with llmstxt.org).
     */
    private function detectFormat(ServerRequestInterface $request): ?string
    {
        // Check for URL suffix format (set by UrlSuffixMiddleware)
        $suffixFormat = $request->getAttribute(UrlSuffixMiddleware::REQUEST_ATTRIBUTE);
        if ($suffixFormat === self::FORMAT_MARKDOWN) {
            return self::FORMAT_MARKDOWN;
        }

        return null;
    }

    /**
     * Generate a unique cache key for the page/language/format combination.
     */
    private function generateCacheKey(int $pageId, int $languageId, string $format): string
    {
        return 'llmstxt_' . $pageId . '_' . $languageId . '_' . $format;
    }

    private function getPageId(ServerRequestInterface $request): ?int
    {
        // Try to get page ID from routing (PageArguments object in TYPO3)
        $routing = $request->getAttribute('routing');

        if ($routing instanceof \TYPO3\CMS\Core\Routing\PageArguments) {
            return $routing->getPageId();
        }

        // Fallback: check if it's an array with pageId
        if (\is_array($routing) && isset($routing['pageId'])) {
            return (int)$routing['pageId'];
        }

        return null;
    }

    private function getLanguage(ServerRequestInterface $request): ?SiteLanguage
    {
        return $request->getAttribute('language');
    }

    private function getBaseUrl(ServerRequestInterface $request): string
    {
        $site = $request->getAttribute('site');
        if ($site !== null) {
            return rtrim((string)$site->getBase(), '/');
        }

        $uri = $request->getUri();
        return $uri->getScheme() . '://' . $uri->getHost();
    }

    /**
     * Extract language ID from SiteLanguage object.
     * Uses toArray() to avoid Extension Scanner "weak" warnings for getLanguageId().
     */
    private function extractLanguageId(?SiteLanguage $language): int
    {
        if (!$language instanceof SiteLanguage) {
            return 0;
        }

        $languageConfig = $language->toArray();

        return (int)($languageConfig['languageId'] ?? 0);
    }
}
