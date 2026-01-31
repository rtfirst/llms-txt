<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RTfirst\LlmsTxt\Service\LlmsTxtGeneratorService;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Middleware that serves llms.txt dynamically with API key protection and caching.
 *
 * Intercepts requests to /llms.txt and returns the generated content.
 * Supports API key authentication when configured.
 */
final readonly class LlmsTxtMiddleware implements MiddlewareInterface
{
    private const API_KEY_HEADER = 'X-LLM-API-Key';
    private const API_KEY_QUERY = 'api_key';
    private const CACHE_KEY_PREFIX = 'llmstxt_index_';

    public function __construct(
        private LlmsTxtGeneratorService $generatorService,
        private FrontendInterface $cache,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Only handle /llms.txt requests
        $path = $request->getUri()->getPath();
        if ($path !== '/llms.txt') {
            return $handler->handle($request);
        }

        // Get site from request
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $handler->handle($request);
        }

        // Check API key protection
        $authResponse = $this->checkApiKeyAuth($request, $site);
        if ($authResponse instanceof ResponseInterface) {
            return $authResponse;
        }

        // Generate cache key based on site identifier
        $cacheKey = self::CACHE_KEY_PREFIX . $site->getIdentifier();

        // Try to get from cache
        if ($this->cache->has($cacheKey)) {
            $cachedContent = $this->cache->get($cacheKey);
            if (\is_string($cachedContent) && $cachedContent !== '') {
                return $this->createResponse($cachedContent, true);
            }
        }

        // Generate llms.txt content
        $content = $this->generatorService->getContentForSite($site);

        if ($content === '') {
            // No content available, pass to next handler (will likely 404)
            return $handler->handle($request);
        }

        // Store in cache with site tag for targeted invalidation
        $this->cache->set(
            $cacheKey,
            $content,
            ['site_' . $site->getIdentifier()],
        );

        return $this->createResponse($content, false);
    }

    /**
     * Create the llms.txt response.
     */
    private function createResponse(string $content, bool $cacheHit): ResponseInterface
    {
        // Add UTF-8 BOM for proper encoding detection
        $utf8Bom = "\xEF\xBB\xBF";

        $response = new Response();
        $response->getBody()->write($utf8Bom . $content);

        return $response
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withHeader('X-Content-Format', 'llms.txt')
            ->withHeader('X-Robots-Tag', 'noindex')
            ->withHeader('X-Cache', $cacheHit ? 'HIT' : 'MISS');
    }

    /**
     * Check API key authentication if configured.
     *
     * @return ResponseInterface|null Returns error response if auth fails, null if auth passes
     */
    private function checkApiKeyAuth(ServerRequestInterface $request, Site $site): ?ResponseInterface
    {
        $settings = $site->getSettings()->getAll();
        $configuredApiKey = trim((string)($settings['llmsTxt']['apiKey'] ?? ''));

        // No API key configured = public access
        if ($configuredApiKey === '') {
            return null;
        }

        // Get API key from request (header or query parameter)
        $providedApiKey = $this->getApiKeyFromRequest($request);

        // Check if API key matches
        if ($providedApiKey === '' || !hash_equals($configuredApiKey, $providedApiKey)) {
            return new JsonResponse(
                [
                    'error' => 'Unauthorized',
                    'message' => 'Valid API key required. Provide via X-LLM-API-Key header or api_key query parameter.',
                ],
                401,
            );
        }

        return null;
    }

    /**
     * Extract API key from request header or query parameter.
     */
    private function getApiKeyFromRequest(ServerRequestInterface $request): string
    {
        // Try header first (preferred)
        $headerKey = $request->getHeaderLine(self::API_KEY_HEADER);
        if ($headerKey !== '') {
            return $headerKey;
        }

        // Fallback to query parameter
        $queryParams = $request->getQueryParams();

        return trim((string)($queryParams[self::API_KEY_QUERY] ?? ''));
    }
}
