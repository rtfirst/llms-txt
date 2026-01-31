# TYPO3 Extension: llms_txt

Generates `llms.txt` for AI/LLM crawlers - a compact index of your website with SEO metadata and instructions for accessing page content in any language.

## Concept

The extension provides a two-tier approach for LLM content access:

1. **llms.txt** - A single index file containing:
   - Website metadata (title, description, domain)
   - Page structure with SEO descriptions and keywords
   - Instructions for accessing full page content

2. **Format Parameters** - Access actual page content via:
   - `?format=clean` - Semantic HTML without CSS/JS/navigation
   - `?format=md` - Clean Markdown with YAML frontmatter

## Multi-Language Support

Instead of generating separate llms.txt files per language, this extension uses a simpler approach:

- **Single llms.txt** - Contains the site structure in the default language
- **Language-specific content** - Access any page in any language using the language URL prefix:
  - Default: `https://example.com/about/?format=md`
  - English: `https://example.com/en/about/?format=md`
  - German: `https://example.com/de/about/?format=md`

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

### Markdown (`?format=md`)

Returns clean Markdown with YAML frontmatter. Example:

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

Simply use the language prefix in the URL:

```
# German (default)
https://example.com/ueber-uns/?format=md

# English
https://example.com/en/about/?format=md

# French
https://example.com/fr/a-propos/?format=md
```

## Example llms.txt Output

```markdown
# My Website

> Your expert for quality products and services.

**Domain:** https://example.com
**Language:** de
**Generated:** 2026-01-31 12:00:00

## LLM-Optimized Content Access

This site provides two LLM-friendly output formats for all pages:

### Clean HTML
Semantic HTML without CSS/JS/navigation. Best for RAG systems.
- **URL-Parameter:** `?format=clean`
- **Example:** `https://example.com/page-slug/?format=clean`

### Markdown
Plain Markdown with YAML frontmatter. Best for text processing.
- **URL-Parameter:** `?format=md`
- **Example:** `https://example.com/page-slug/?format=md`

### Multi-Language Access
To access content in different languages, use the language-specific URL prefix:
- **Default language:** `https://example.com/page/?format=md`
- **English:** `https://example.com/en/page/?format=md`
- **Other languages:** Use the configured language prefix (e.g., `/de/`, `/fr/`)

## Page Structure

- **[Home](/)**
  Welcome to our website with all important information.
  `/?format=clean` | `/?format=md`

  - **[About](/about/)**
    Learn about our company history and values.
    `/about/?format=clean` | `/about/?format=md`

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
