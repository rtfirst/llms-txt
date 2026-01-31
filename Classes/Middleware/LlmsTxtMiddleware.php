<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RTfirst\LlmsTxt\Service\LlmsTxtGeneratorService;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
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
    use ApiKeyAuthenticationTrait;

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

        // Check API key protection (via ApiKeyAuthenticationTrait)
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
}
