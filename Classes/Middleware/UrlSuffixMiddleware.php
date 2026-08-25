<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Routing\SiteRouteResult;

/**
 * Middleware that detects .md suffix in URLs and rewrites them for routing.
 *
 * This middleware runs BEFORE the page resolver to:
 * 1. Detect URLs ending with .md (e.g., /page.md or /page/index.html.md)
 * 2. Strip the suffix and set a request attribute for format detection
 * 3. Allow normal TYPO3 routing to find the actual page
 *
 * If the target site has Markdown output disabled (llmsTxt.enableMarkdown),
 * the .md suffix is left untouched so normal TYPO3 routing 404s it instead
 * of exposing any Markdown/content-format handling further down the stack.
 *
 * Spec-compliant with https://llmstxt.org/
 */
final readonly class UrlSuffixMiddleware implements MiddlewareInterface
{
    public const REQUEST_ATTRIBUTE = 'llms_txt_format';
    private const MARKDOWN_SUFFIX = '.md';
    private const INDEX_SUFFIX = '/index.html.md';

    public function __construct(
        private SiteMatcher $siteMatcher,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        $isIndexSuffix = str_ends_with($path, self::INDEX_SUFFIX);
        $isMarkdownSuffix = !$isIndexSuffix && str_ends_with($path, self::MARKDOWN_SUFFIX);

        if (!$isIndexSuffix && !$isMarkdownSuffix) {
            return $handler->handle($request);
        }

        // Site isn't resolved yet at this point in the middleware stack (this
        // middleware runs before the site resolver on purpose), so the site
        // has to be matched independently to read its Markdown setting.
        if (!$this->isMarkdownEnabledForRequest($request)) {
            return $handler->handle($request);
        }

        if ($isIndexSuffix) {
            // /page/index.html.md -> /page/
            $newPath = substr($path, 0, -\strlen(self::INDEX_SUFFIX) + 1);
            $request = $this->rewriteRequest($request, $newPath);
        } else {
            // /page.md -> /page or /page/.md -> /page/
            $newPath = substr($path, 0, -\strlen(self::MARKDOWN_SUFFIX));
            // Handle /page/.md edge case
            if (str_ends_with($newPath, '/.')) {
                $newPath = substr($newPath, 0, -1);
            }
            // Ensure path doesn't become empty
            if ($newPath === '') {
                $newPath = '/';
            }
            $request = $this->rewriteRequest($request, $newPath);
        }

        return $handler->handle($request);
    }

    /**
     * Whether Markdown output is enabled for the site matching this request.
     *
     * Defaults to true (enabled) if no site can be matched yet, matching the
     * setting's own default and preserving prior behaviour for edge cases.
     */
    private function isMarkdownEnabledForRequest(ServerRequestInterface $request): bool
    {
        $routeResult = $this->siteMatcher->matchRequest($request);
        if (!$routeResult instanceof SiteRouteResult) {
            return true;
        }

        return (bool)$routeResult->getSite()->getSettings()->get('llmsTxt.enableMarkdown', true);
    }

    /**
     * Rewrite the request with new path and set format attribute.
     */
    private function rewriteRequest(ServerRequestInterface $request, string $newPath): ServerRequestInterface
    {
        $uri = $request->getUri()->withPath($newPath);

        return $request
            ->withUri($uri)
            ->withAttribute(self::REQUEST_ATTRIBUTE, 'md');
    }
}
