# Changelog

All notable changes to the llms_txt extension will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-01-31

### Added

- Initial release of the LLMs.txt Generator extension for TYPO3 13/14
- Automatic generation of `llms.txt` files for AI/LLM crawlers
- Two LLM-friendly output formats for all pages:
  - `?format=clean` - Semantic HTML without CSS/JS/navigation
  - `?format=md` - Markdown with YAML frontmatter
- Multi-language support with language-specific URL prefixes
- Content converters for TYPO3 content elements:
  - Header (`CType=header`)
  - Text, Textpic, Textmedia (`CType=text`, `textpic`, `textmedia`)
  - Image (`CType=image`)
  - Table (`CType=table`)
  - Bullets/Lists (`CType=bullets`)
  - HTML (`CType=html`)
  - Menu elements (`CType=menu_*`)
  - Default fallback for other content types
- Page properties for LLM optimization:
  - `tx_llmstxt_description` - LLM-specific page description
  - `tx_llmstxt_summary` - Extended page summary
  - `tx_llmstxt_keywords` - Keywords for LLM indexing
  - `tx_llmstxt_exclude` - Exclude page from llms.txt
  - `tx_llmstxt_priority` - Page priority (0-100) for sorting
- Site settings for configuration:
  - `llmsTxt.baseUrl` - Custom base URL
  - `llmsTxt.intro` - Website description/intro text
  - `llmsTxt.excludePages` - Comma-separated page UIDs to exclude
  - `llmsTxt.includeHidden` - Include hidden pages
- Automatic regeneration on cache flush via event listener
- Backend notification if robots.txt is missing llms.txt reference
- 24-hour caching for format outputs to reduce database load
- UTF-8 BOM in generated files for proper encoding detection

### Technical

- PHP 8.2+ required
- TYPO3 13.0 - 14.x compatible
- Uses `league/html-to-markdown` for HTML to Markdown conversion
- PSR-12 compliant code style
- PHPStan Level 8 compliant
- Full unit test coverage for converters and services
- Extension Scanner compatible (no deprecated API usage)
