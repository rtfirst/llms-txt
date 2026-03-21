# Changelog

All notable changes to the rt_llms_txt extension will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.9] - 2026-03-21

### Fixed

- **Security:** Replace `removeAll()` query restrictions with selective restriction management — now properly respects `fe_group`, `starttime`, and `endtime` access controls
- **Bug:** Cache flush event now clears entire llms.txt cache (index + per-page Markdown), not just index entries
- **Bug:** Escape pipe characters in table cell content to prevent broken Markdown tables
- **Bug:** YAML frontmatter escaping now handles newlines, carriage returns, and tabs
- **Bug:** Escape Markdown link syntax characters in page titles within llms.txt output
- **Bug:** Apply YAML escaping to canonical URLs in Markdown frontmatter

### Changed

- Add `LoggerInterface` to `AbstractContentConverter` — file reference errors are now logged instead of silently swallowed
- Add `LoggerInterface` to `PageTreeService` — URL generation fallbacks are now logged with exception details
- Use `new FlashMessage()` instead of `GeneralUtility::makeInstance()` in `BackendNotificationEventListener`
- Remove redundant `getSettings()` call in `LlmsTxtGeneratorService` — settings are now retrieved once and passed through
- Simplify `CacheFlushEventListener` — remove unused `LlmsTxtGeneratorService` dependency

## [1.0.8] - 2026-03-21

### Changed

- Rename TYPO3 extension key from `llms_txt` to `rt_llms_txt` (Composer package name `rtfirst/llms-txt` remains unchanged)
- Update all `EXT:llms_txt/` references to `EXT:rt_llms_txt/`
- Add explicit extension key to TER publish workflow

## [1.0.7] - 2026-02-06

### Added

- GitHub Actions workflow for automatic TER publishing on version tags
- CI workflow reusable via `workflow_call` for TER publish pipeline
- Missing CHANGELOG entries for versions 1.0.5 and 1.0.6

### Fixed

- Version mismatch in `Documentation/guides.xml` (was 1.0.5, now synced)

## [1.0.6] - 2026-02-03

### Changed

- Add comprehensive RST documentation (ApiProtection, Configuration, Developer, FAQ, Usage, etc.)

### Fixed

- Rector: unused foreach value warning in `LlmsTxtGeneratorService`
- PHP-CS-Fixer: coding style issue (`in_array` native function import)

## [1.0.5] - 2026-02-02

### Added

- Append `.md` suffix to internal page links in Markdown output for consistent LLM navigation
- File link detection to avoid `.md` suffix on file downloads (PDF, images, etc.)
- HTML entity decoding in Markdown output (`&amp;` → `&`, `&lt;` → `<`)
- Convert remaining HTML tags (`<br>`, `<a>`, `<strong>`, `<em>`) to Markdown before stripping
- Dynamic example page URL in llms.txt (uses first real page instead of placeholder)
- Make canonical URLs absolute in Markdown frontmatter
- Unit test for file links without `.md` suffix

### Changed

- Improved Markdown cleanup: convert inline HTML to Markdown equivalents before `strip_tags()`
- Reorder link conversion: process images before page links to avoid false matches

## [1.0.4] - 2026-02-01

### Added

- Unit tests for HeaderLinkEventListener

### Changed

- Update dev dependencies: phpstan ^2.1, phpstan-typo3 ^2.0, rector ^2.0
- Apply Rector fixes (First Class Callable syntax)

### Fixed

- TYPO3 14 compatibility for HeaderLinkEventListener (use getContent()/setContent())
- PHPStan errors for TYPO3 13/14 compatibility
- GitHub repository URLs in composer.json

## [1.0.3] - 2026-02-01

### Added

- HeaderLinkEventListener for theme-independent header link injection
- Convert relative links to absolute URLs in Markdown output
- Badges and support links to README and composer.json
- Unit tests for new functionality

### Fixed

- Header link not appearing when no API key configured

## [1.0.2] - 2026-01-31

### Changed

- Apply Rector code quality suggestions

## [1.0.1] - 2026-01-31

### Changed

- Code quality improvements

## [1.0.0] - 2026-01-31

### Added

- Initial release of the LLMs.txt Generator extension for TYPO3 13/14
- **Spec-compliant** with [llmstxt.org](https://llmstxt.org/) specification
- **Dynamic llms.txt serving** via middleware with caching
- **Markdown output format** for all pages via `.md` URL suffix
  - Example: `/about.md` returns Markdown with YAML frontmatter
  - `UrlSuffixMiddleware` for URL rewriting before routing
  - Root page accessible via `/index.html.md`
- **API key protection** for all LLM endpoints (`/llms.txt` and `.md` suffix)
  - Optional protection via site setting `llmsTxt.apiKey`
  - Supports header authentication: `X-LLM-API-Key: your-key`
  - Supports query parameter: `?api_key=your-key`
  - Returns 401 Unauthorized with JSON error for invalid/missing key
  - Authentication documentation included in llms.txt when API key is configured
- **Multi-language support** with language-specific URL prefixes
  - Default: `/about.md`
  - English: `/en/about.md`
  - German: `/de/ueber-uns.md`
- **Page properties** for LLM optimization (dedicated "LLM" tab):
  - `tx_llmstxt_description` - LLM-specific page description
  - `tx_llmstxt_summary` - Extended page summary
  - `tx_llmstxt_keywords` - Keywords for LLM indexing
  - `tx_llmstxt_exclude` - Exclude page from llms.txt
  - `tx_llmstxt_priority` - Page priority (0-100) for sorting
- **Site settings** for configuration:
  - `llmsTxt.baseUrl` - Custom base URL
  - `llmsTxt.intro` - Website description/intro text
  - `llmsTxt.excludePages` - Comma-separated page UIDs to exclude
  - `llmsTxt.includeHidden` - Include hidden pages
  - `llmsTxt.apiKey` - API key for protected access
- **HTML header link** (`<link rel="alternate">`) pointing to llms.txt
  - Automatically hidden when API key protection is enabled
- **Backend notification** if robots.txt is missing llms.txt reference
- **24-hour caching** for Markdown output to reduce database load
- **Content filtering** for clean Markdown output:
  - Removes Bootstrap "visually-hidden" accessibility spans
  - Removes empty anchor tags (e.g., `<a id="c1"></a>`)
  - Removes scripts, styles, navigation, footer, sidebars
- **UTF-8 BOM** in generated content for proper encoding detection

### Technical

- PHP 8.2+ required
- TYPO3 13.0 - 14.x compatible
- Uses `league/html-to-markdown` for HTML to Markdown conversion
- PSR-12 compliant code style
- PHPStan Level 8 compliant
- Full unit test coverage for converters and services
- Extension Scanner compatible (no deprecated API usage)
