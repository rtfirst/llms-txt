<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Trait for API key authentication in middleware classes.
 *
 * Provides methods to check API key authentication from site settings
 * and extract API keys from request headers or query parameters.
 */
trait ApiKeyAuthenticationTrait
{
    private const API_KEY_HEADER = 'X-LLM-API-Key';
    private const API_KEY_QUERY = 'api_key';

    /**
     * Check API key authentication if configured.
     *
     * @return ResponseInterface|null Returns error response if auth fails, null if auth passes
     */
    private function checkApiKeyAuth(ServerRequestInterface $request, ?Site $site = null): ?ResponseInterface
    {
        if (!$site instanceof Site) {
            $site = $request->getAttribute('site');
        }

        if (!$site instanceof Site) {
            return null;
        }

        $settings = $site->getSettings()->getAll();
        $configuredApiKey = trim((string)($settings['llmsTxt']['apiKey'] ?? ''));

        // No API key configured = public access
        if ($configuredApiKey === '') {
            return null;
        }

        // Get API key from request (header or query parameter)
        $providedApiKey = $this->getApiKeyFromRequest($request);

        // Check if API key matches (timing-safe comparison)
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
