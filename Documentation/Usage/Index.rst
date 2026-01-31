..  include:: /Includes.rst.txt

..  _usage:

=====
Usage
=====

Generated File
==============

After clearing the TYPO3 cache, the extension generates ``llms.txt`` in
the ``public/`` directory.

Example llms.txt Output
-----------------------

..  code-block:: markdown

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
    - **Example:** `https://example.com/page-slug.md`

    ### Multi-Language Access
    Use language-specific URL prefixes with the `.md` suffix:
    - **Default language:** `https://example.com/page.md`
    - **English:** `https://example.com/en/page.md`
    - **Other languages:** Use configured prefix (e.g., `/de/page.md`, `/fr/page.md`)

    ## Page Structure

    - **[Home](/)**
      Welcome to our website with all important information.
      [Markdown](/index.html.md)

      - **[About](/about/)**
        Learn about our company history and values.
        [Markdown](/about.md)

      - **[Services](/services/)**
        Professional services for your needs.
        *Keywords: services, consulting, support*
        [Markdown](/services.md)

Content Access Format
=====================

Markdown Format
---------------

Access any page by appending ``.md`` to the URL (spec-compliant with llmstxt.org):

..  code-block:: text

    https://example.com/about.md

This returns clean Markdown with YAML frontmatter:

..  code-block:: markdown

    ---
    title: "About Us"
    description: "Learn about our company..."
    language: en
    date: 2026-01-31
    canonical: "https://example.com/about/"
    format: markdown
    generator: "TYPO3 LLMs.txt Extension"
    ---

    # About Us

    > Learn about our company...

    Our company was founded in 1985...

    ## Our Values

    - Quality and reliability
    - Fair prices

Multi-Language Access
---------------------

To access content in different languages, use the language prefix with the ``.md`` suffix:

..  code-block:: text

    # German (default language)
    https://example.com/ueber-uns.md

    # English
    https://example.com/en/about.md

    # French
    https://example.com/fr/a-propos.md

The ``.md`` suffix works with any language URL.

HTML Header Link
================

The extension automatically adds a link tag to all HTML pages:

..  code-block:: html

    <link rel="alternate" type="text/plain" href="/llms.txt" title="LLM Content Guide">

This helps AI crawlers discover the ``llms.txt`` file from any page.

Supported Content Elements
==========================

The extension converts the following TYPO3 content elements to Markdown:

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *  - Content Type
       - Handling

    *  - Header
       - Converted to Markdown heading with subheader as italic text

    *  - Text
       - RTE content converted to Markdown

    *  - Text & Images
       - Text content plus image references with alt text

    *  - Text & Media
       - Text content plus media asset references

    *  - Image
       - Image references with alt text

    *  - HTML
       - HTML converted to Markdown

    *  - Bullets
       - Unordered, ordered, or definition lists

    *  - Table
       - Converted to Markdown table format

    *  - Menu elements
       - Skipped (navigation already in llms.txt structure)

    *  - Other elements
       - Header and bodytext extracted if available

Regenerating Files
==================

The ``llms.txt`` file is automatically regenerated when:

-  All caches are cleared
-  The pages cache is cleared

You can manually trigger regeneration:

..  code-block:: bash

    # TYPO3 CLI
    vendor/bin/typo3 cache:flush

    # DDEV
    ddev typo3 cache:flush

Caching Behavior
================

The Markdown output (``.md`` suffix) is cached separately for 24 hours to reduce
database load. This means:

-  The base page is cached normally by TYPO3
-  The Markdown format output is cached separately
-  Cache is invalidated when the page content changes

The response includes an ``X-Robots-Tag: noindex`` header to prevent search
engines from indexing the Markdown format.
