:navigation-title: Usage

..  _usage:

=====
Usage
=====

This chapter explains how to access the LLM-optimized content.

..  _usage-llms-txt:

Accessing llms.txt
==================

The llms.txt index file is available at the root of your website:

..  code-block:: text

   https://example.com/llms.txt

This file contains:

-   Website metadata (title, description, domain)
-   Page structure with descriptions and keywords
-   Instructions for accessing page content in Markdown format (if
    :ref:`llmsTxt.enableMarkdown <confval-enableMarkdown>` is enabled)

..  _usage-llms-txt-example:

Example llms.txt Output
-----------------------

..  code-block:: markdown

   # My Website

   > Your expert for quality products and services.

   **Specification:** <https://llmstxt.org/>
   **Domain:** https://example.com
   **Language:** de
   **Generated:** 2026-01-31 12:00:00

   This site provides LLM-friendly Markdown output for all pages.

   **Markdown Format:** Append `.md` to any page URL to get plain Markdown with YAML frontmatter.
   - **Example:** `https://example.com/about.md`

   ## Page Structure

   - [Home](https://example.com/): Welcome to our website with all important information. [Markdown](https://example.com/index.html.md)
     - [About](https://example.com/about): Learn about our company history and values. [Markdown](https://example.com/about.md)
     - [Services](https://example.com/services): Professional services for your needs. Keywords: services, consulting, support. [Markdown](https://example.com/services.md)
     - [Contact](https://example.com/contact): Get in touch with us via phone or email. [Markdown](https://example.com/contact.md)

Each page is one line ``- [Title](url): notes`` as defined by
`llmstxt.org <https://llmstxt.org/>`__. The notes contain the description,
summary and keywords of the page and the link to its Markdown version, so the
file can be read by llms.txt parsers.

With :ref:`llmsTxt.enableMarkdown <confval-enableMarkdown>` disabled, the
Markdown hints (including the authentication hints) and the ``[Markdown](…)``
links are omitted.

..  _usage-markdown:

Accessing Markdown Content
==========================

Append ``.md`` to any page URL to get the content as clean Markdown with
YAML frontmatter.

..  code-block:: text

   https://example.com/about.md

..  note::

   The Markdown output is enabled by default. It can be turned off per site
   with :ref:`llmsTxt.enableMarkdown <confval-enableMarkdown>`, ``.md`` URLs
   then return a regular 404.

..  _usage-markdown-output:

Example Markdown Output
-----------------------

..  code-block:: markdown

   ---
   title: "About Us"
   description: "Learn about our company history and values."
   language: en
   date: 2024-06-15
   lastmod: 2026-01-31
   canonical: "/about"
   format: markdown
   generator: "TYPO3 LLMs.txt Extension"
   ---

   # About Us

   > Learn about our company history and values.

   ## Our History

   Our company was founded in 1985...

   ## Our Values

   - Quality and reliability
   - Fair and transparent prices
   - Personal consultation

The YAML frontmatter contains:

-   **title**: Page title
-   **description**: Page description (from LLM or SEO settings)
-   **language**: ISO language code
-   **date**: Page creation date (from TYPO3 ``crdate``)
-   **lastmod**: Last modification date (most recent change across page and content elements); omitted if same as ``date``
-   **canonical**: Canonical URL path
-   **format**: Output format (always "markdown")
-   **generator**: Extension identifier

..  _usage-root-page:

Accessing the Root Page
-----------------------

For the root/home page, use:

..  code-block:: text

   https://example.com/index.html.md

Or simply:

..  code-block:: text

   https://example.com/.md

..  _usage-multi-language:

Multi-Language Access
=====================

Access page content in different languages using the language URL prefix
with the ``.md`` suffix:

..  code-block:: text

   # German (default language)
   https://example.com/ueber-uns.md

   # English
   https://example.com/en/about.md

   # French
   https://example.com/fr/a-propos.md

The extension automatically:

-   Detects the language from the URL prefix
-   Loads the translated page content
-   Sets the correct language in the YAML frontmatter

..  _usage-caching:

Caching
=======

The extension uses smart caching for optimal performance:

-   **llms.txt**: Cached and regenerated when TYPO3 cache is cleared or the
    ``llmsTxt.*`` site settings change
-   **Markdown output**: Cached for 24 hours per page/language combination

To force regeneration:

..  code-block:: bash

   vendor/bin/typo3 cache:flush

Or in DDEV:

..  code-block:: bash

   ddev typo3 cache:flush

..  _usage-content-filtering:

Content Filtering
=================

The Markdown output is automatically cleaned for better LLM consumption:

**Removed elements:**

-   Scripts and styles
-   Navigation and footer elements
-   Sidebar content
-   Bootstrap accessibility spans (``visually-hidden``)
-   Empty anchor tags (``<a id="c1"></a>``)

**Preserved elements:**

-   Main content text
-   Headings and structure
-   Lists and tables
-   Images (converted to Markdown syntax)
-   Links (converted to absolute URLs)
