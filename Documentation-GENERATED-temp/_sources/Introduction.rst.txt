:navigation-title: Introduction

..  _introduction:

============
Introduction
============

What is llms.txt?
=================

``llms.txt`` is an emerging standard for providing AI and Large Language Model (LLM)
crawlers with structured information about a website. It serves as a machine-readable
index that helps AI systems understand your website's content and structure.

The `llmstxt.org specification <https://llmstxt.org/>`__ defines how websites can
provide this information in a standardized format.

Concept
=======

This extension provides a two-tier approach for LLM content access:

1.  **llms.txt Index File** - A single file containing:

    -   Website metadata (title, description, domain)
    -   Page structure with SEO descriptions and keywords
    -   Instructions for accessing full page content

2.  **Markdown Content Access** - Access any page content via:

    -   ``.md`` suffix - Returns clean Markdown with YAML frontmatter
    -   Example: ``/about.md`` returns the "About" page as Markdown

Multi-Language Support
======================

Instead of generating separate ``llms.txt`` files per language, this extension
uses a simpler approach:

-   **Single llms.txt** - Contains the site structure in the default language
-   **Language-specific content** - Access any page in any language using the
    ``.md`` suffix with language URL prefix:

    -   Default: ``https://example.com/about.md``
    -   English: ``https://example.com/en/about.md``
    -   German: ``https://example.com/de/ueber-uns.md``

This approach follows how multi-language sites actually work in TYPO3.

Features
========

Core Features
-------------

-   **Automatic llms.txt generation** with smart caching
-   **Markdown output** for all pages via ``.md`` URL suffix
-   **Multi-language support** via URL prefixes
-   **API key protection** for restricted access
-   **YAML frontmatter** in Markdown output with page metadata

Page-Level Control
------------------

-   **LLM tab** in page properties for fine-grained control
-   **Custom descriptions** and summaries per page
-   **Keywords** for better LLM understanding
-   **Priority setting** (0-100) for page ordering
-   **Exclude option** to hide specific pages from llms.txt

Technical Features
------------------

-   **24-hour caching** for optimal performance
-   **HTML-to-Markdown conversion** using League/html-to-markdown
-   **Clean output** - removes scripts, styles, navigation elements
-   **UTF-8 BOM** for proper encoding detection
-   **Backend notification** if robots.txt lacks llms.txt reference
-   **Header link injection** (``<link rel="alternate">``) in HTML pages

Requirements
============

-   TYPO3 13.0 - 14.x
-   PHP 8.2 or higher

Supported Content Elements
==========================

The extension converts the following TYPO3 content elements to Markdown:

-   **Header** (``header``)
-   **Text** (``text``)
-   **Text with Image** (``textpic``, ``textmedia``)
-   **Image** (``image``)
-   **Bullet List** (``bullets``)
-   **Table** (``table``)
-   **HTML** (``html``)
-   **Menu** elements (``menu_*``)
-   All other elements via HTML-to-Markdown fallback
