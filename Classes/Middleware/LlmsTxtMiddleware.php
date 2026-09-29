<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RTfirst\LlmsTxt\Service\LlmsTxtGeneratorService;
use RTfirst\LlmsTxt\Utility\LlmsTxtPath;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Middleware that serves llms.txt dynamically with API key protection and caching.
 *
 * Intercepts requests to the llms.txt of a site language (/llms.txt, /en/llms.txt, ...)
 * and returns the generated content. Supports API key authentication when configured.
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
        // Only handle llms.txt requests
        $path = $request->getUri()->getPath();
        if (!str_ends_with($path, '/' . LlmsTxtPath::FILE_NAME)) {
            return $handler->handle($request);
        }

        // Get site and language from request (resolved by the site resolver)
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $handler->handle($request);
        }
        $language = $request->getAttribute('language');
        if (!$language instanceof SiteLanguage) {
            $language = $site->getDefaultLanguage();
        }

        // Every language has its llms.txt below its base, e.g. /llms.txt or /en/llms.txt.
        // Other paths, including those of unknown or disabled languages, are not handled.
        if ($path !== LlmsTxtPath::forLanguage($language)) {
            return $handler->handle($request);
        }

        // Check API key protection (via ApiKeyAuthenticationTrait)
        $authResponse = $this->checkApiKeyAuth($request, $site);
        if ($authResponse instanceof ResponseInterface) {
            return $authResponse;
        }

        // Generate cache key based on site identifier, language and llms.txt settings
        $cacheKey = self::CACHE_KEY_PREFIX . $site->getIdentifier()
            . '_' . (int)($language->toArray()['languageId'] ?? 0)
            . '_' . $this->getSettingsHash($site);

        // Try to get from cache
        if ($this->cache->has($cacheKey)) {
            $cachedContent = $this->cache->get($cacheKey);
            if (\is_string($cachedContent) && $cachedContent !== '') {
                return $this->createResponse($cachedContent, true);
            }
        }

        // Generate llms.txt content
        $content = $this->generatorService->getContentForSite($site, $language);

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
     * Short hash of the llms.txt site settings and the site languages for the cache key.
     *
     * Saving the site settings (e.g. in the backend settings editor) or the site
     * configuration does not flush the pages cache group, so a changed setting
     * (enableMarkdown, intro, excludePages, ...) or language (intro of the language,
     * title and base in the links to the other languages, ...) has to lead to a new
     * cache entry by itself.
     */
    private function getSettingsHash(Site $site): string
    {
        $settings = $site->getSettings()->getAll()['llmsTxt'] ?? [];
        $languages = $site->getConfiguration()['languages'] ?? [];

        return substr(md5(serialize([$settings, $languages])), 0, 10);
    }

    /**
     * Create the llms.txt response.
     */
    private function createResponse(string $content, bool $cacheHit): ResponseInterface
    {
        // No UTF-8 BOM: the Content-Type header declares the encoding, and a BOM
        // in front of the H1 breaks llmstxt.org parsers
        $response = new Response();
        $response->getBody()->write($content);

        return $response
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withHeader('X-Content-Format', 'llms.txt')
            ->withHeader('X-Robots-Tag', 'noindex')
            ->withHeader('X-Cache', $cacheHit ? 'HIT' : 'MISS');
    }
}
