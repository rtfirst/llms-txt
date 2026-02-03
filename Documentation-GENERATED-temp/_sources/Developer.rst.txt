:navigation-title: Developer

..  _developer:

=====================
Developer Information
=====================

This chapter provides technical details for developers and integrators.

..  _developer-architecture:

Architecture
============

The extension uses three PSR-15 middlewares:

1.  **UrlSuffixMiddleware** - Detects ``.md`` suffix and rewrites URLs
2.  **LlmsTxtMiddleware** - Serves the ``/llms.txt`` endpoint
3.  **ContentFormatMiddleware** - Transforms HTML to Markdown

..  _developer-middlewares:

Middleware Chain
----------------

..  code-block:: text

   Request: /about.md
      │
      ▼
   UrlSuffixMiddleware (before site resolver)
      │  Strips .md suffix
      │  Sets request attribute 'llms_txt_format' = 'md'
      │  Rewrites URI to /about
      ▼
   TYPO3 Site Resolver
      │
      ▼
   LlmsTxtMiddleware (after site, before page resolver)
      │  Handles /llms.txt requests
      ▼
   TYPO3 Page Resolver & Frontend
      │
      ▼
   ContentFormatMiddleware (after content-length-headers)
      │  Checks for 'llms_txt_format' attribute
      │  Converts HTML response to Markdown
      ▼
   Response: Markdown with YAML frontmatter

..  _developer-services:

Services
========

PageTreeService
---------------

``RTfirst\LlmsTxt\Service\PageTreeService``

Traverses the TYPO3 page tree and collects page data for llms.txt generation.

-   Supports multi-language sites
-   Respects page exclusion settings
-   Handles translated pages with fallback

MarkdownConverterService
------------------------

``RTfirst\LlmsTxt\Service\MarkdownConverterService``

Orchestrates content element to Markdown conversion using registered converters.

LlmsTxtGeneratorService
-----------------------

``RTfirst\LlmsTxt\Service\LlmsTxtGeneratorService``

Generates the llms.txt content for a site.

..  _developer-converters:

Content Converters
==================

The extension uses a converter pattern for content element to Markdown conversion.
Each converter implements ``ContentConverterInterface``:

..  code-block:: php

   interface ContentConverterInterface
   {
       public function supports(string $cType): bool;
       public function convert(array $record, string $baseUrl): string;
   }

Built-in Converters
-------------------

.. list-table::
   :header-rows: 1
   :widths: 30 40 30

   * - Converter
     - Supported CTypes
     - Description
   * - HeaderConverter
     - ``header``
     - Converts header elements
   * - TextConverter
     - ``text``, ``textpic``, ``textmedia``
     - Converts text and text+media
   * - ImageConverter
     - ``image``
     - Converts image galleries
   * - BulletsConverter
     - ``bullets``
     - Converts bullet lists
   * - TableConverter
     - ``table``
     - Converts tables
   * - MenuConverter
     - ``menu_*``
     - Converts menu elements
   * - HtmlConverter
     - ``html``
     - Converts raw HTML
   * - DefaultConverter
     - (fallback)
     - HTML-to-Markdown fallback

..  _developer-custom-converter:

Creating Custom Converters
--------------------------

1.  Create a class implementing ``ContentConverterInterface``:

..  code-block:: php

   <?php
   declare(strict_types=1);

   namespace Vendor\MyExtension\Converter;

   use RTfirst\LlmsTxt\Converter\ContentConverterInterface;

   class MyCustomConverter implements ContentConverterInterface
   {
       public function supports(string $cType): bool
       {
           return $cType === 'my_custom_element';
       }

       public function convert(array $record, string $baseUrl): string
       {
           $header = $record['header'] ?? '';
           $bodytext = $record['bodytext'] ?? '';

           return "## {$header}\n\n{$bodytext}";
       }
   }

2.  Register the converter in ``Services.yaml``:

..  code-block:: yaml

   services:
     Vendor\MyExtension\Converter\MyCustomConverter:
       tags:
         - name: 'llms_txt.content_converter'
           priority: 100

Higher priority converters are checked first.

..  _developer-events:

Event Listeners
===============

CacheFlushEventListener
-----------------------

Invalidates llms.txt cache when TYPO3 caches are flushed.

HeaderLinkEventListener
-----------------------

Injects the ``<link rel="alternate">`` tag into HTML responses.

BackendNotificationEventListener
--------------------------------

Shows a notification in the Backend if robots.txt lacks llms.txt reference.

..  _developer-caching:

Caching
=======

The extension uses two cache layers:

1.  **llms.txt Index Cache** (``cache_pages``)

    -   Stores generated llms.txt content per site
    -   Invalidated on cache flush

2.  **Format Output Cache** (``llms_txt_format``)

    -   Stores Markdown output per page/language
    -   24-hour default lifetime
    -   Part of the ``pages`` cache group

..  _developer-database:

Database Schema
===============

The extension adds fields to the ``pages`` table:

.. list-table::
   :header-rows: 1
   :widths: 30 20 50

   * - Field
     - Type
     - Description
   * - tx_llmstxt_description
     - text
     - LLM-specific page description
   * - tx_llmstxt_summary
     - text
     - Extended page summary
   * - tx_llmstxt_keywords
     - varchar(255)
     - Comma-separated keywords
   * - tx_llmstxt_exclude
     - tinyint(1)
     - Exclude from llms.txt
   * - tx_llmstxt_priority
     - int(11)
     - Priority (0-100) for sorting

..  _developer-code-quality:

Code Quality
============

The extension maintains high code quality standards:

-   **PHPStan Level 8** compliant
-   **PSR-12** code style (php-cs-fixer)
-   **Unit tests** for converters and services

Run quality checks:

..  code-block:: bash

   # Static analysis
   vendor/bin/phpstan analyse packages/llms_txt --level=8

   # Code style check
   vendor/bin/php-cs-fixer fix packages/llms_txt --dry-run

   # Fix code style
   vendor/bin/php-cs-fixer fix packages/llms_txt

   # Run tests
   vendor/bin/phpunit -c packages/llms_txt/phpunit.xml

..  _developer-dependencies:

Dependencies
============

-   **league/html-to-markdown** (^5.1) - HTML to Markdown conversion
-   **typo3/cms-core** (^13.0 || ^14.0)
-   **typo3/cms-frontend** (^13.0 || ^14.0)
