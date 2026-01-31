..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

Site Settings
=============

After adding the "LLMs.txt Generator" Site Set to your site, you can configure
the extension via Site Settings.

..  confval:: llmsTxt.baseUrl
    :type: string
    :default: (empty)

    The full URL of your website (e.g., ``https://example.com``).

    This is required for CLI-based generation and ensures correct absolute URLs
    in the generated ``llms.txt`` file.

    ..  note::

        If not set, the extension tries to detect the base URL from the site
        configuration or the current request.

..  confval:: llmsTxt.intro
    :type: text
    :default: (empty)

    A description of your website that appears in the intro section of the
    ``llms.txt`` file. This helps AI systems understand the purpose of your site.

    Example::

        Your expert for quality tires and professional service since 1985.

..  confval:: llmsTxt.excludePages
    :type: string
    :default: (empty)

    Comma-separated list of page UIDs to exclude from the ``llms.txt`` file.

    Example::

        42,123,456

..  confval:: llmsTxt.includeHidden
    :type: bool
    :default: false

    If enabled, hidden pages will be included in the ``llms.txt`` file.

    ..  warning::

        Use with caution. Hidden pages are typically hidden for a reason.

..  confval:: llmsTxt.apiKey
    :type: string
    :default: (empty)

    Optional API key to protect ``/llms.txt`` and the ``.md`` suffix endpoint.

    When set, requests to these endpoints require authentication via:

    -  **Header**: ``X-LLM-API-Key: your-key``
    -  **Query parameter**: ``?api_key=your-key``

    Unauthenticated requests receive a 401 Unauthorized response.

    ..  note::

        When an API key is configured, the ``<link rel="alternate">`` header
        tag is automatically hidden from HTML pages. This prevents exposing
        protected endpoints to public crawlers.

    Leave empty for public access (default).

Page Properties
===============

Each page has an "LLM" tab with fields specifically for controlling how the
page appears in the ``llms.txt`` file.

..  confval:: Exclude from llms.txt
    :type: checkbox

    When checked, this page (and its subpages) will not appear in the
    ``llms.txt`` file.

..  confval:: LLM Priority
    :type: number (0-100)
    :default: 0

    Pages with higher priority values appear first in the ``llms.txt`` file.
    Use this to highlight your most important pages.

    -  0 = Normal priority
    -  50 = Medium priority
    -  100 = Highest priority

..  confval:: LLM Description
    :type: text
    :max: 500 characters

    A custom description for this page, optimized for AI understanding.
    If empty, falls back to the meta description or abstract.

..  confval:: LLM Summary
    :type: text
    :max: 2000 characters

    An extended summary of the page content. Displayed as a blockquote in
    the ``llms.txt`` file.

..  confval:: LLM Keywords
    :type: string
    :max: 255 characters

    Comma-separated keywords describing the page topics.

    Example::

        tires, winter tires, summer tires, tire service

robots.txt Configuration
========================

To help AI crawlers discover your ``llms.txt`` file, add these lines to your
``public/robots.txt``:

..  code-block:: text

    # LLM Content Guide
    Sitemap: https://your-domain.com/llms.txt
    Sitemap: https://your-domain.com/llms-en.txt

    # Allow AI crawlers access to llms.txt
    User-agent: GPTBot
    Allow: /llms.txt
    Allow: /llms-en.txt

    User-agent: Claude-Web
    Allow: /llms.txt
    Allow: /llms-en.txt

    User-agent: Anthropic-AI
    Allow: /llms.txt
    Allow: /llms-en.txt

    User-agent: *
    Allow: /llms.txt
    Allow: /llms-en.txt

..  tip::

    The extension shows a notification in the TYPO3 backend if your
    ``robots.txt`` doesn't reference ``llms.txt``.

Excluded Page Types
===================

The following page types (doktypes) are automatically excluded from the
``llms.txt`` file:

-  Folder (254)
-  Recycler (255)
-  Spacer (199)
-  Backend User Section (6)
