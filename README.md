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

Every enabled language of a site has its own `llms.txt` below its base:

- Default language: `https://example.com/llms.txt`
- English with the base `/en/`: `https://example.com/en/llms.txt`
- A language with its own domain: `https://example.co.uk/llms.txt`

Each file lists the pages in its language, with translated titles, descriptions and URLs (with `fallbackType: strict`, untranslated pages are left out). A `## Languages` section links to the `llms.txt` of the other languages, so crawlers find all of them from any file. A language without pages has no `llms.txt` (404).

The intro of each language is set in the site configuration (see [Site Languages](#site-languages)). Page content in any language is available with the `.md` suffix:

- Default: `https://example.com/about.md`
- English: `https://example.com/en/about.md`

## Features

- **Automatic generation** of llms.txt when TYPO3 cache is cleared or the llms.txt site settings change
- **One llms.txt per language**, linked to each other
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
| `llmsTxt.intro` | Website description shown in the intro section of the default language (see [Site Languages](#site-languages)) |
| `llmsTxt.excludePages` | Comma-separated page UIDs to exclude |
| `llmsTxt.includeHidden` | Include hidden pages (default: false) |
| `llmsTxt.enableMarkdown` | Serve the `.md` Markdown variant and reference it in llms.txt (default: true). If disabled, `.md` URLs return 404 and llms.txt contains no Markdown section or links |
| `llmsTxt.apiKey` | API key for protected access (empty = public access) |

### Site Languages

In the **Sites** module, each language of a site has this field:

| Field | Description |
|-------|-------------|
| **llms.txt Intro** | Website description shown in the intro section of the `llms.txt` of this language (`llmsTxtIntro` in `config.yaml`). It takes precedence over `llmsTxt.intro`, which is the fallback for the default language only. Other languages without this field have no intro |

### Page Properties (LLM Tab)

Each page has an "LLM" tab with these fields:

| Field | Description |
|-------|-------------|
| **Exclude from llms.txt** | Don't include this page in the index |
| **LLM Priority** | Higher values (0-100) appear first among the pages with the same parent page. Translations use the priority of the default language page |
| **LLM Description** | Custom description (fallback: meta description) |
| **LLM Summary** | Additional summary text, added to the notes of the page in llms.txt |
| **LLM Keywords** | Comma-separated topics for this page |

## Output File

`llms.txt` is not written to `public/`. The extension serves it dynamically at `/llms.txt` of each site and below the base of every further language (e.g. `/en/llms.txt`), and caches the generated content until the TYPO3 cache is flushed or the `llmsTxt.*` site settings or the site languages change. A static `public/llms.txt` file would be delivered by the web server instead, so do not create one.

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

This site provides LLM-friendly Markdown output for all content pages.

**Markdown Format:** Append `.md` to a page URL to get plain Markdown with YAML frontmatter. Pages that only link to another page or URL are listed without a Markdown link.
- **Example:** `https://example.com/about.md`

## Languages

- [English](https://example.com/en/llms.txt): en

## Page Structure

- [Home](https://example.com/): Welcome to our website with all important information. [Markdown](https://example.com/index.html.md)
  - [About](https://example.com/about): Learn about our company history and values. [Markdown](https://example.com/about.md)
  - [Services](https://example.com/services): Professional services for your needs. Keywords: services, consulting, support. [Markdown](https://example.com/services.md)
  - [Contact](https://example.com/contact): Get in touch with us via phone or email. [Markdown](https://example.com/contact.md)
  - [Partner](https://example.com/partner): Our partner for logistics.
```

Each page is one line `- [Title](url): notes` as defined by [llmstxt.org](https://llmstxt.org/). The notes contain the description, summary and keywords of the page and the link to its Markdown version, so the file can be read by llms.txt parsers. Pages of type "Link" and "Shortcut" only point to another page or URL and have no content of their own, so they are listed without the Markdown link. The `## Languages` section links to the `llms.txt` of the other languages; it is omitted on single-language sites. With `llmsTxt.enableMarkdown` disabled, the Markdown hints and the `[Markdown](…)` links are omitted.

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

Add the `llms.txt` of further languages as well, e.g. `Allow: /en/llms.txt`.

## HTML Header Link

The extension automatically adds a link tag to all HTML pages:

```html
<link rel="alternate" type="text/plain" href="/llms.txt" title="LLM Content Guide">
```

This helps AI crawlers discover the `llms.txt` file from any page. The link points to the `llms.txt` of the page language, e.g. `/en/llms.txt` on English pages.

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

The extension includes a GitHub Actions workflow (`.github/workflows/ci.yaml`) that runs for pushes to and pull requests against `main` and `develop`:
- PHP-CS-Fixer (code style)
- PHPStan Level 8 (static analysis, TYPO3 13)
- Rector (code modernization)
- Unit and functional tests (PHP 8.2-8.5, TYPO3 13 & 14)
- TER artefact check (classic mode, TYPO3 13 & 14)

Publishing to the TER only happens for version tags (`.github/workflows/ter-publish.yaml`), which run the same checks first.

## Author

**Roland Tfirst**
Email: roland@tfirst.de

## License

GPL-2.0-or-later
