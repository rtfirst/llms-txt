:navigation-title: FAQ

..  _faq:

================================
Frequently Asked Questions (FAQ)
================================

..  _faq-general:

General Questions
=================

..  _faq-what-is-llms-txt:

What is llms.txt?
-----------------

``llms.txt`` is an emerging standard for providing AI and Large Language Model
(LLM) crawlers with structured information about a website. It helps AI systems
understand your website's content and structure. See the
`llmstxt.org specification <https://llmstxt.org/>`__ for details.

..  _faq-why-use:

Why should I use this extension?
--------------------------------

-   **Better AI understanding** of your website content
-   **Structured access** for chatbots and RAG systems
-   **Clean Markdown output** without navigation, scripts, or styling
-   **Multi-language support** out of the box
-   **Optional protection** with API keys

..  _faq-installation:

Installation Questions
======================

..  _faq-404-error:

I get a 404 error when accessing /llms.txt
------------------------------------------

Make sure you have:

1.  Added the **Site Set** to your site configuration
2.  Cleared all caches: ``vendor/bin/typo3 cache:flush``

See :ref:`installation-site-set` for details.

..  _faq-no-llm-tab:

The LLM tab doesn't appear in page properties
---------------------------------------------

1.  Verify the extension is installed: ``vendor/bin/typo3 extension:list``
2.  Clear the system cache
3.  Log out and log back into the TYPO3 Backend

..  _faq-configuration:

Configuration Questions
=======================

..  _faq-exclude-pages:

How do I exclude specific pages?
--------------------------------

You have two options:

1.  **Site Settings**: Add page UIDs to ``llmsTxt.excludePages`` (comma-separated)
2.  **Per Page**: Check "Exclude from llms.txt" in the page's LLM tab

..  _faq-priority:

How does the priority setting work?
-----------------------------------

Pages with higher priority values (0-100) appear earlier in the llms.txt
page list. Use this to highlight important pages for AI crawlers:

-   80-100: Main landing pages
-   50-70: Important content
-   20-40: Secondary pages
-   0-10: Low priority

..  _faq-hidden-pages:

Can I include hidden pages?
---------------------------

Yes, enable ``llmsTxt.includeHidden`` in the Site Settings. This is useful
for staging environments.

..  _faq-usage:

Usage Questions
===============

..  _faq-root-page-md:

How do I access the root page as Markdown?
------------------------------------------

Use ``/index.html.md`` or ``/.md``:

..  code-block:: text

   https://example.com/index.html.md

..  _faq-language-access:

How do I access translated pages?
---------------------------------

Use the language prefix with the ``.md`` suffix:

..  code-block:: text

   # English
   https://example.com/en/about.md

   # German
   https://example.com/de/ueber-uns.md

..  _faq-cache-refresh:

How do I refresh the cached content?
------------------------------------

Clear the TYPO3 cache:

..  code-block:: bash

   vendor/bin/typo3 cache:flush

Or in DDEV:

..  code-block:: bash

   ddev typo3 cache:flush

..  _faq-api-protection:

API Protection Questions
========================

..  _faq-api-key-header:

Which HTTP header should I use for the API key?
-----------------------------------------------

Use ``X-LLM-API-Key``:

..  code-block:: bash

   curl -H "X-LLM-API-Key: your-key" https://example.com/llms.txt

..  _faq-api-key-query:

Can I use a query parameter instead?
------------------------------------

Yes, use ``api_key``:

..  code-block:: text

   https://example.com/llms.txt?api_key=your-key

However, HTTP headers are recommended for security reasons.

..  _faq-troubleshooting:

Troubleshooting
===============

..  _faq-empty-markdown:

The Markdown output is empty
----------------------------

-   Check if the page has content elements
-   Verify the page is not excluded from llms.txt
-   Clear all caches

..  _faq-wrong-language:

Content shows in wrong language
-------------------------------

-   Verify your site language configuration
-   Check if translations exist for the page
-   Use the correct language prefix in the URL

..  _faq-missing-content:

Some content elements are missing
---------------------------------

The extension filters out:

-   Navigation elements
-   Footer content
-   Scripts and styles
-   Empty elements

If a specific content type is missing, you may need to create a custom
converter. See :ref:`developer-custom-converter`.
