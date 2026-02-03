:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

The extension can be configured via Site Settings and page properties.

..  _configuration-site-settings:

Site Settings
=============

After adding the Site Set to your site configuration (see :ref:`installation-site-set`),
you can configure the extension in **Site Management > Settings**.

..  confval-menu::
   :display: table
   :type:

.. _confval-baseUrl:

.. confval:: llmsTxt.baseUrl

   :type: string
   :Default: (empty)

   Full URL of the website (e.g., ``https://example.com``). This is used as
   the base URL in the generated llms.txt file. If empty, the site's base URL
   from the site configuration is used.

.. _confval-intro:

.. confval:: llmsTxt.intro

   :type: text
   :Default: (empty)

   Website description shown in the intro section of the llms.txt file.
   This text appears as a blockquote below the site title and helps AI
   crawlers understand the purpose of your website.

   Example::

      Your expert for tires, wheels, and automotive services since 1985.

.. _confval-excludePages:

.. confval:: llmsTxt.excludePages

   :type: string
   :Default: (empty)

   Comma-separated list of page UIDs to exclude from the llms.txt index.
   Use this for pages that should not appear in the LLM index, such as
   imprint, privacy policy, or internal pages.

   Example::

      42,56,123

.. _confval-includeHidden:

.. confval:: llmsTxt.includeHidden

   :type: boolean
   :Default: false

   If enabled, hidden pages are also included in the llms.txt generation.
   This can be useful for staging environments or preview purposes.

.. _confval-apiKey:

.. confval:: llmsTxt.apiKey

   :type: string
   :Default: (empty)

   API key for protected access to ``/llms.txt`` and ``.md`` endpoints.
   If set, requests without a valid API key will receive a 401 Unauthorized
   response. Leave empty for public access.

   See :ref:`api-protection` for details on how to use API key protection.

..  _configuration-page-properties:

Page Properties
===============

Each page has an **LLM** tab in the page properties with the following fields:

..  confval-menu::
   :display: table
   :type:

.. _confval-exclude:

.. confval:: Exclude from llms.txt

   :type: checkbox
   :Default: false

   If enabled, this page will not appear in the llms.txt index. The page
   is also excluded from the Markdown output.

.. _confval-priority:

.. confval:: LLM Priority

   :type: number (slider)
   :Default: 0
   :Range: 0-100

   Higher values (0-100) cause the page to appear earlier in the llms.txt
   page list. Use this to highlight important pages for AI crawlers.

   **Recommendations:**

   -  **80-100**: Main landing pages, key services
   -  **50-70**: Important content pages
   -  **20-40**: Secondary pages
   -  **0-10**: Low-priority pages

.. _confval-description:

.. confval:: LLM Description

   :type: textarea
   :Default: (empty)
   :Max length: 500 characters

   Custom description for this page in the llms.txt index. If empty,
   the page's SEO meta description (from the SEO tab) is used as fallback.

   This description helps AI crawlers understand what the page is about.

.. _confval-summary:

.. confval:: LLM Summary

   :type: textarea
   :Default: (empty)
   :Max length: 2000 characters

   Additional summary text shown as a blockquote in the llms.txt index.
   Use this for longer explanations that don't fit in the description.

.. _confval-keywords:

.. confval:: LLM Keywords

   :type: text input
   :Default: (empty)
   :Max length: 255 characters

   Comma-separated keywords/topics for this page. These appear in the
   llms.txt index and help AI crawlers categorize the page content.

   Example::

      tires, wheels, alignment, services

..  _configuration-robots-txt:

robots.txt Configuration
========================

To allow AI crawlers to discover and access your llms.txt file, add these
lines to your ``public/robots.txt``:

..  code-block:: text

   # Allow AI crawlers to access llms.txt
   User-agent: GPTBot
   Allow: /llms.txt

   User-agent: Claude-Web
   Allow: /llms.txt

   User-agent: Anthropic-AI
   Allow: /llms.txt

   User-agent: Google-Extended
   Allow: /llms.txt

..  note::

   The extension shows a notification in the TYPO3 Backend if your robots.txt
   does not contain a reference to llms.txt.

..  _configuration-header-link:

HTML Header Link
================

The extension automatically adds a ``<link>`` tag to all HTML pages to help
AI crawlers discover the llms.txt file:

..  code-block:: html

   <link rel="alternate" type="text/plain" href="/llms.txt" title="LLM Content Guide">

..  note::

   When API key protection is enabled, this header link is automatically
   hidden to prevent unauthorized crawlers from discovering the endpoint.
