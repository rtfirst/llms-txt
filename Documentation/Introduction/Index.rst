..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

What is llms.txt?
=================

The ``llms.txt`` file is a proposed standard for providing AI and LLM (Large
Language Model) crawlers with structured information about a website. Similar
to how ``robots.txt`` guides traditional web crawlers, ``llms.txt`` helps AI
systems understand your website's content and structure.

Concept
=======

This extension provides a two-tier approach for LLM content access:

1. **llms.txt Index File**

   A single index file containing:

   -  Website metadata (title, description, domain)
   -  Page structure with SEO descriptions and keywords
   -  Instructions for accessing full page content

2. **Format Parameters**

   Access actual page content via URL parameters:

   -  ``?format=clean`` - Semantic HTML without CSS/JS/navigation
   -  ``?format=md`` - Clean Markdown with YAML frontmatter

Features
========

Automatic Generation
--------------------

The extension automatically generates ``llms.txt`` when the TYPO3 cache
is cleared. No manual intervention required.

Multi-Language Support
----------------------

Instead of generating separate files per language, this extension uses URL-based
language access:

-  **Single llms.txt** - Contains the site structure in the default language
-  **Language-specific content** - Access any page in any language using the
   language URL prefix with ``?format=clean`` or ``?format=md``

Examples:

-  Default: ``https://example.com/about/?format=md``
-  English: ``https://example.com/en/about/?format=md``
-  German: ``https://example.com/de/ueber-uns/?format=md``

Page Properties
---------------

A dedicated "LLM" tab in page properties allows editors to configure:

-  LLM-specific descriptions
-  Keywords and summaries
-  Priority for ordering
-  Exclusion from the index

Clean Output Formats
--------------------

Both ``?format=clean`` and ``?format=md`` provide well-formatted content
without:

-  CSS stylesheets
-  JavaScript code
-  Navigation elements
-  Footer content
-  Excessive whitespace

HTML Header Link
----------------

The extension adds a ``<link rel="alternate">`` tag to all HTML pages,
pointing to the ``llms.txt`` file.

Use Cases
=========

RAG Systems
-----------

Retrieval-Augmented Generation (RAG) systems can use the clean HTML or
Markdown output to index your website content efficiently.

AI Assistants
-------------

AI assistants like ChatGPT, Claude, or custom solutions can discover your
website structure and access content in a format optimized for processing.

Content Aggregation
-------------------

Services that aggregate content for AI training or analysis can use the
structured ``llms.txt`` file to understand your site hierarchy.
