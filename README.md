# TYPO3 Extension: rt_llms_txt

[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)
[![TYPO3 14](https://img.shields.io/badge/TYPO3-14-orange.svg)](https://get.typo3.org/version/14)
[![Latest Stable Version](https://poser.pugx.org/rtfirst/llms-txt/v/stable)](https://packagist.org/packages/rtfirst/llms-txt)
[![CI](https://github.com/rtfirst/llms-txt/actions/workflows/ci.yaml/badge.svg)](https://github.com/rtfirst/llms-txt/actions/workflows/ci.yaml)
[![Total Downloads](https://poser.pugx.org/rtfirst/llms-txt/downloads)](https://packagist.org/packages/rtfirst/llms-txt)
[![License](https://poser.pugx.org/rtfirst/llms-txt/license)](https://packagist.org/packages/rtfirst/llms-txt)

Generates `llms.txt` for AI/LLM crawlers - a compact index of your website with SEO metadata and instructions for accessing page content in any language. Optionally protect access with an API key.

> **Note:** This extension implements the [llmstxt.org specification](https://llmstxt.org/).

## Concept

The extension provides a two-tier approach for LLM content access: 

1. **llms.txt** - A single index file containing:
   - Website metadata (title, description, domain)
   - Page structure with SEO descriptions and keywords
   - Instructions for accessing full page content

2. **Content Format** - Access page content via (spec-compliant with llmstxt.org):
   - `.md` suffix - Clean Markdown (e.g., `/page.md`)
   - Enabled by default, can be turned off per site with `llmsTxt.enableMarkdown`

## Multi-Language Support

Instead of generating separate llms.txt files per language, this extension uses a simpler approach:

- **Single llms.txt** - Contains the site structure in the default language
- **Language-specific content** - Access any page in any language using the `.md` suffix with language URL prefix:
  - Default: `https://example.com/about.md`
  - English: `https://example.com/en/about.md`
  - German: `https://example.com/de/about.md`

This approach is cleaner and follows how multi-language sites actually work.

## Features

- **Automatic generation** of llms.txt when TYPO3 cache is cleared or the llms.txt site settings change
- **Page properties tab**: Configure LLM-specific metadata for each page
- **HTML header link**: Adds `<link rel="alternate">` to HTML pages
- **Clean output formats**: Well-formatted HTML and Markdown without excessive whitespace
- **Flexible configuration**: Via Site Settings and page properties

## Requirements

- TYPO3 13.0 - 14.x
- PHP 8.2+

## Installation

```bash
composer require rtfirst/llms-txt
```

Then activate the extension:

```bash
ddev typo3 extension:setup
ddev typo3 cache:flush
```

### Classic mode (without Composer)

Install the extension from the [TER](https://extensions.typo3.org/extension/rt_llms_txt) via the Extension Manager,
or upload `rt_llms_txt_<version>.zip` from the assets of a [GitHub release](https://github.com/rtfirst/llms-txt/releases).
These packages bundle the required library [league/html-to-markdown](https://github.com/thephpleague/html-to-markdown)
(MIT license) in `Resources/Private/Php/ComposerVendor/`. The "Source code" archives of GitHub releases do not.

## Configuration

### Site Settings

Add the Site Set "LLMs.txt Generator" to your site configuration, then configure in Site Settings:

| Setting | Description |
|---------|-------------|
| `llmsTxt.baseUrl` | Full URL of the website (e.g., `https://example.com`) |
| `llmsTxt.intro` | Website description shown in the intro section |
| `llmsTxt.excludePages` | Comma-separated page UIDs to exclude |
| `llmsTxt.includeHidden` | Include hidden pages (default: false) |
| `llmsTxt.enableMarkdown` | Serve the `.md` Markdown variant and reference it in llms.txt (default: true). If disabled, `.md` URLs return 404 and llms.txt contains no Markdown section or links |
| `llmsTxt.apiKey` | API key for protected access (empty = public access) |

### Page Properties (LLM Tab)

Each page has an "LLM" tab with these fields:

| Field | Description |
|-------|-------------|
| **Exclude from llms.txt** | Don't include this page in the index |
| **LLM Priority** | Higher values (0-100) appear first in the list |
| **LLM Description** | Custom description (fallback: meta description) |
| **LLM Summary** | Additional summary text shown as quote |
| **LLM Keywords** | Comma-separated topics for this page |

## Output File

`llms.txt` is not written to `public/`. The extension serves it dynamically at `/llms.txt` of each site and caches the generated content until the TYPO3 cache is flushed or the `llmsTxt.*` site settings change. A static `public/llms.txt` file would be delivered by the web server instead, so do not create one.

## Content Access Formats

### Markdown (`.md` suffix)

Returns clean Markdown with YAML frontmatter. Spec-compliant with llmstxt.org.

```
https://example.com/about.md
```

Output:
```markdown
---
title: "About Us"
description: "Learn about our company..."
language: en
date: 2024-06-15
lastmod: 2026-01-31
canonical: "/about"
format: markdown
generator: "TYPO3 LLMs.txt Extension"
---

# About Us

> Learn about our company...

## Our History

Our company was founded in 1985...

## Our Values

- Quality and reliability
- Fair and transparent prices
- Personal consultation
```

### Accessing Different Languages

Simply use the language prefix with the `.md` suffix:

```
# German (default)
https://example.com/ueber-uns.md

# English
https://example.com/en/about.md

# French
https://example.com/fr/a-propos.md
```

## API Key Protection

You can protect both `/llms.txt` and the `.md` suffix endpoint with an API key. This is useful when you want to:

- Restrict access to your own chatbots/RAG systems
- Prevent external scraping of structured content
- Control who can access your LLM-optimized content

### Configuration

Set the `llmsTxt.apiKey` in your Site Settings. Leave empty for public access (default).

### Usage

Pass the API key via **HTTP header** (recommended):

```bash
# Access llms.txt
curl -H "X-LLM-API-Key: your-secret-key" https://example.com/llms.txt

# Access page as Markdown
curl -H "X-LLM-API-Key: your-secret-key" https://example.com/about.md
```

Or via **query parameter**:

```
https://example.com/llms.txt?api_key=your-secret-key
https://example.com/about.md?api_key=your-secret-key
```

### n8n Integration

In n8n HTTP Request node, add the header:

| Name | Value |
|------|-------|
| `X-LLM-API-Key` | `your-secret-key` |

### Error Response

Invalid or missing API key returns `401 Unauthorized`:

```json
{
  "error": "Unauthorized",
  "message": "Valid API key required. Provide via X-LLM-API-Key header or api_key query parameter."
}
```

## Example llms.txt Output

```markdown
# My Website

> Your expert for quality products and services.

**Specification:** <https://llmstxt.org/>
**Domain:** https://example.com
**Language:** de
**Generated:** 2026-01-31 12:00:00

## LLM-Optimized Content Access

This site provides LLM-friendly Markdown output for all pages:

### Markdown Format
Append `.md` to any page URL to get plain Markdown with YAML frontmatter.
- **Example:** `https://example.com/about.md`

## Page Structure

- **[Home](https://example.com/)**
  Welcome to our website with all important information.
  [Markdown](https://example.com/index.html.md)

  - **[About](https://example.com/about)**
    Learn about our company history and values.
    [Markdown](https://example.com/about.md)

  - **[Services](https://example.com/services)**
    Professional services for your needs.
    *Keywords: services, consulting, support*
    [Markdown](https://example.com/services.md)

  - **[Contact](https://example.com/contact)**
    Get in touch with us via phone or email.
    [Markdown](https://example.com/contact.md)
```

With `llmsTxt.enableMarkdown` disabled, the "LLM-Optimized Content Access" section and the `[Markdown](…)` links are omitted.

## robots.txt Configuration

Add these lines to your `public/robots.txt` to allow AI crawlers:

```
# Allow AI crawlers to access llms.txt
User-agent: GPTBot
Allow: /llms.txt

User-agent: Claude-Web
Allow: /llms.txt

User-agent: Anthropic-AI
Allow: /llms.txt
```

## HTML Header Link

The extension automatically adds a link tag to all HTML pages:

```html
<link rel="alternate" type="text/plain" href="/llms.txt" title="LLM Content Guide">
```

This helps AI crawlers discover the `llms.txt` file from any page.

## Development

### Code Quality

```bash
# Static analysis (from DDEV project root)
ddev exec vendor/bin/phpstan analyse packages/llms_txt/Classes --level=8

# Code style check
ddev exec vendor/bin/php-cs-fixer fix packages/llms_txt --dry-run

# Fix code style
ddev exec vendor/bin/php-cs-fixer fix packages/llms_txt
```

### Testing

```bash
# Run unit tests (from DDEV project root)
ddev exec "cd packages/llms_txt && ../../vendor/bin/phpunit --bootstrap ../../vendor/autoload.php"
```

### CI Pipeline

The extension includes a GitHub Actions workflow (`.github/workflows/ci.yaml`) that runs:
- PHP-CS-Fixer (code style)
- PHPStan Level 8 (static analysis)
- Rector (code modernization)
- Unit Tests (PHP 8.2-8.4, TYPO3 13 & 14)

## Author

**Roland Tfirst**
Email: roland@tfirst.de

## License

GPL-2.0-or-later
