<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware that detects .md suffix in URLs and rewrites them for routing.
 *
 * This middleware runs BEFORE the page resolver to:
 * 1. Detect URLs ending with .md (e.g., /page.md or /page/index.html.md)
 * 2. Strip the suffix and set a request attribute for format detection
 * 3. Allow normal TYPO3 routing to find the actual page
 *
 * Spec-compliant with https://llmstxt.org/
 */
final class UrlSuffixMiddleware implements MiddlewareInterface
{
    public const REQUEST_ATTRIBUTE = 'llms_txt_format';
    private const MARKDOWN_SUFFIX = '.md';
    private const INDEX_SUFFIX = '/index.html.md';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        // Check for .md suffix
        if (str_ends_with($path, self::INDEX_SUFFIX)) {
            // /page/index.html.md -> /page/
            $newPath = substr($path, 0, -\strlen(self::INDEX_SUFFIX) + 1);
            $request = $this->rewriteRequest($request, $newPath);
        } elseif (str_ends_with($path, self::MARKDOWN_SUFFIX)) {
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
