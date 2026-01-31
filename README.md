# TYPO3 Extension: llms_txt

Generates `llms.txt` for AI/LLM crawlers - a compact index of your website with SEO metadata and instructions for accessing page content in any language.

## Concept

The extension provides a two-tier approach for LLM content access:

1. **llms.txt** - A single index file containing:
   - Website metadata (title, description, domain)
   - Page structure with SEO descriptions and keywords
   - Instructions for accessing full page content

2. **Content Formats** - Access page content via (spec-compliant with llmstxt.org):
   - `.md` suffix - Clean Markdown (e.g., `/page.md`)
   - `?format=clean` - Semantic HTML without CSS/JS/navigation
   - `?format=md` - Markdown via query parameter (fallback)

## Multi-Language Support

Instead of generating separate llms.txt files per language, this extension uses a simpler approach:

- **Single llms.txt** - Contains the site structure in the default language
- **Language-specific content** - Access any page in any language using the `.md` suffix with language URL prefix:
  - Default: `https://example.com/about.md`
  - English: `https://example.com/en/about.md`
  - German: `https://example.com/de/about.md`

This approach is cleaner and follows how multi-language sites actually work.

## Features

- **Automatic generation** of llms.txt when TYPO3 cache is cleared
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

## Configuration

### Site Settings

Add the Site Set "LLMs.txt Generator" to your site configuration, then configure in Site Settings:

| Setting | Description |
|---------|-------------|
| `llmsTxt.baseUrl` | Full URL of the website (e.g., `https://example.com`) |
| `llmsTxt.intro` | Website description shown in the intro section |
| `llmsTxt.excludePages` | Comma-separated page UIDs to exclude |
| `llmsTxt.includeHidden` | Include hidden pages (default: false) |
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

After cache flush, `llms.txt` is created in `public/`.

## Content Access Formats

### Clean HTML (`?format=clean`)

Returns semantic HTML without CSS, JavaScript, or navigation. Example:

```
https://example.com/about/?format=clean
```

Output:
```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About Us</title>
  <meta name="description" content="Learn about our company...">
  <meta name="robots" content="noindex, nofollow">
</head>
<body>
<article>
  <h1>About Us</h1>
  <p>Our company was founded in...</p>
</article>
</body>
</html>
```

### Markdown (`.md` suffix - recommended)

Returns clean Markdown with YAML frontmatter. Spec-compliant with llmstxt.org.

```
https://example.com/about.md
```

Alternative (query parameter):

```
https://example.com/about/?format=md
```

Output:
```markdown
---
title: "About Us"
description: "Learn about our company..."
language: en
date: 2026-01-31
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

You can protect the `.md` suffix and `?format=clean` / `?format=md` endpoints with an API key. This is useful when you want to:

- Restrict access to your own chatbots/RAG systems
- Prevent external scraping of structured content
- Control who can access your LLM-optimized content

### Configuration

Set the `llmsTxt.apiKey` in your Site Settings. Leave empty for public access (default).

### Usage

Pass the API key via **HTTP header** (recommended):

```bash
curl -H "X-LLM-API-Key: your-secret-key" https://example.com/about.md
```

Or via **query parameter**:

```
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

**Domain:** https://example.com
**Language:** de
**Generated:** 2026-01-31 12:00:00

## LLM-Optimized Content Access

This site provides LLM-friendly output formats for all pages:

### Markdown (Recommended)
Append `.md` to any page URL to get plain Markdown with YAML frontmatter.
- **Example:** `https://example.com/page-slug.md`
- **Alternative:** `?format=md` query parameter

### Clean HTML
Semantic HTML without CSS/JS/navigation. Best for RAG systems.
- **URL-Parameter:** `?format=clean`
- **Example:** `https://example.com/page-slug/?format=clean`

### Multi-Language Access
Use language-specific URL prefixes with the `.md` suffix:
- **Default language:** `https://example.com/page.md`
- **English:** `https://example.com/en/page.md`
- **Other languages:** Use configured prefix (e.g., `/de/page.md`, `/fr/page.md`)

## Page Structure

- **[Home](/)**
  Welcome to our website with all important information.
  [Markdown](/index.html.md) | [Clean HTML](/?format=clean)

  - **[About](/about/)**
    Learn about our company history and values.
    [Markdown](/about.md) | [Clean HTML](/about/?format=clean)

  - **[Services](/services/)**
    Professional services for your needs.
    *Keywords: services, consulting, support*
    `/services/?format=clean` | `/services/?format=md`

- **[Contact](/contact/)**
  Get in touch with us via phone or email.
  `/contact/?format=clean` | `/contact/?format=md`
```

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
# Static analysis
ddev exec vendor/bin/phpstan analyse packages/llms_txt --level=8

# Code style check
ddev exec vendor/bin/php-cs-fixer fix packages/llms_txt --dry-run

# Fix code style
ddev exec vendor/bin/php-cs-fixer fix packages/llms_txt
```

### Testing

```bash
ddev exec vendor/bin/phpunit -c packages/llms_txt/phpunit.xml
```

## Author

**Roland Tfirst**
Email: roland@tfirst.de

## License

GPL-2.0-or-later
