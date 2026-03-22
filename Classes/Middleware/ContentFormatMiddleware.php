<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Middleware;

use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\ParameterType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RTfirst\LlmsTxt\Service\MarkdownRendererService;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
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
        private ConnectionPool $connectionPool,
        private LoggerInterface $logger,
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
        if ($authResponse instanceof ResponseInterface) {
            return $authResponse;
        }

        // Get page information from request (available before handler)
        $pageId = $this->getPageId($request);
        $language = $this->getLanguage($request);

        if ($pageId === null) {
            return $handler->handle($request);
        }

        // Generate cache key (includes path hash to differentiate plugin views)
        $languageId = $this->extractLanguageId($language);
        $requestPath = $request->getUri()->getPath();
        $cacheKey = $this->generateCacheKey($pageId, $languageId, $format, $requestPath);

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

        // Fetch page timestamps for frontmatter (graceful fallback on DB errors)
        $createdAt = 0;
        $modifiedAt = 0;
        try {
            $timestamps = $this->getPageTimestamps($pageId, $languageId);
            $createdAt = $timestamps['crdate'];
            $contentLastModified = $this->getContentLastModified($pageId, $languageId);
            // Prefer content timestamps (editorial changes) over pages.tstamp
            // (which gets updated by system operations like cache flush or extension setup)
            $modifiedAt = $contentLastModified > 0 ? $contentLastModified : $timestamps['tstamp'];
        } catch (DbalException $e) {
            $this->logger->error('Failed to fetch timestamps for page {pageId}', [
                'pageId' => $pageId,
                'exception' => $e->getMessage(),
            ]);
        }

        // Render as Markdown
        $content = $this->markdownRenderer->render($originalHtml, $pageId, $language, $baseUrl, $createdAt, $modifiedAt);
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
     * Generate a unique cache key for the page/language/format/path combination.
     *
     * Includes a hash of the request path to differentiate plugin views
     * (e.g., news list vs. news detail) on the same page.
     */
    private function generateCacheKey(int $pageId, int $languageId, string $format, string $requestPath = ''): string
    {
        $key = 'llmstxt_' . $pageId . '_' . $languageId . '_' . $format;
        if ($requestPath !== '' && $requestPath !== '/') {
            $key .= '_' . substr(md5($requestPath), 0, 10);
        }

        return $key;
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
     * Fetch page timestamps from the database.
     *
     * For non-default languages, queries the translation overlay record.
     * If no overlay exists, falls back to default-language crdate but sets
     * tstamp to 0 so that lastmod is determined solely by language-specific
     * content timestamps (prevents cross-language timestamp bleeding).
     * Uses only DeletedRestriction since the page was already confirmed
     * accessible by TYPO3 routing.
     *
     * @return array{crdate: int, tstamp: int}
     */
    private function getPageTimestamps(int $pageId, int $languageId = 0): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());

        if ($languageId > 0) {
            $row = $queryBuilder
                ->select('crdate', 'tstamp')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageId, ParameterType::INTEGER)),
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, ParameterType::INTEGER)),
                )
                ->executeQuery()
                ->fetchAssociative();

            if ($row !== false) {
                return [
                    'crdate' => (int)$row['crdate'],
                    'tstamp' => (int)$row['tstamp'],
                ];
            }

            // No overlay: use default-language crdate for date field, but tstamp=0
            // so lastmod reflects only language-specific content changes
            $defaultTimestamps = $this->getPageTimestamps($pageId, 0);
            return ['crdate' => $defaultTimestamps['crdate'], 'tstamp' => 0];
        }

        $row = $queryBuilder
            ->select('crdate', 'tstamp')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, ParameterType::INTEGER)))
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            $this->logger->warning('Could not fetch timestamps for page {pageId}', ['pageId' => $pageId]);
            return ['crdate' => 0, 'tstamp' => 0];
        }

        return [
            'crdate' => (int)$row['crdate'],
            'tstamp' => (int)$row['tstamp'],
        ];
    }

    /**
     * Get the most recent modification timestamp from content elements on a page.
     *
     * Uses only DeletedRestriction so that hiding/restricting a content element
     * is correctly reflected as a modification. Filters by language.
     */
    private function getContentLastModified(int $pageId, int $languageId = 0): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());

        $result = $queryBuilder
            ->addSelectLiteral($queryBuilder->expr()->max('tstamp', 'max_tstamp'))
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, ParameterType::INTEGER)),
            )
            ->executeQuery()
            ->fetchOne();

        // MAX() returns NULL when no rows match; (int) cast handles this safely
        return (int)$result;
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
