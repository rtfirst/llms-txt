:navigation-title: API Protection

..  _api-protection:

==============
API Protection
==============

You can protect both ``/llms.txt`` and the ``.md`` suffix endpoints with
an API key. This is useful when you want to:

-   Restrict access to your own chatbots or RAG systems
-   Prevent external scraping of structured content
-   Control who can access your LLM-optimized content

..  _api-protection-setup:

Setting Up API Protection
=========================

1.  Go to **Site Management > Settings** in the TYPO3 Backend.

2.  Find the **LLMs-Text** category.

3.  Enter your API key in the **API Key for Format Access** field.

4.  Save and clear all caches.

..  tip::

   Generate a secure API key with:

   ..  code-block:: bash

      php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

   Or in DDEV:

   ..  code-block:: bash

      ddev exec php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

..  _api-protection-usage:

Authenticating Requests
=======================

Pass the API key via **HTTP header** (recommended):

..  code-block:: bash

   # Access llms.txt
   curl -H "X-LLM-API-Key: your-secret-key" https://example.com/llms.txt

   # Access page as Markdown
   curl -H "X-LLM-API-Key: your-secret-key" https://example.com/about.md

Or via **query parameter**:

..  code-block:: text

   https://example.com/llms.txt?api_key=your-secret-key
   https://example.com/about.md?api_key=your-secret-key

..  warning::

   Using query parameters exposes the API key in server logs and browser
   history. Prefer the HTTP header method for production use.

..  _api-protection-error:

Error Response
==============

Invalid or missing API key returns **HTTP 401 Unauthorized** with a JSON body:

..  code-block:: json

   {
     "error": "Unauthorized",
     "message": "Valid API key required. Provide via X-LLM-API-Key header or api_key query parameter."
   }

..  _api-protection-integrations:

Integration Examples
====================

n8n Integration
---------------

In n8n HTTP Request node, add the header:

.. list-table::
   :header-rows: 1
   :widths: 30 70

   * - Name
     - Value
   * - ``X-LLM-API-Key``
     - ``your-secret-key``

Python Integration
------------------

..  code-block:: python

   import requests

   headers = {
       "X-LLM-API-Key": "your-secret-key"
   }

   # Get llms.txt
   response = requests.get("https://example.com/llms.txt", headers=headers)
   print(response.text)

   # Get page as Markdown
   response = requests.get("https://example.com/about.md", headers=headers)
   print(response.text)

JavaScript/Node.js Integration
------------------------------

..  code-block:: javascript

   const response = await fetch("https://example.com/llms.txt", {
     headers: {
       "X-LLM-API-Key": "your-secret-key"
     }
   });

   const content = await response.text();
   console.log(content);

cURL Integration
----------------

..  code-block:: bash

   # Store API key in environment variable
   export LLM_API_KEY="your-secret-key"

   # Access llms.txt
   curl -H "X-LLM-API-Key: $LLM_API_KEY" https://example.com/llms.txt

   # Access multiple pages
   for page in about services contact; do
     curl -H "X-LLM-API-Key: $LLM_API_KEY" "https://example.com/${page}.md" > "${page}.md"
   done

..  _api-protection-behavior:

Behavior When Enabled
=====================

When API key protection is enabled:

1.  **llms.txt** requires authentication
2.  **All .md endpoints** require authentication
3.  The **HTML header link** (``<link rel="alternate">``) is automatically hidden
4.  The llms.txt file includes **authentication instructions**

..  _api-protection-disable:

Disabling API Protection
========================

To make endpoints publicly accessible again:

1.  Go to **Site Management > Settings**
2.  Clear the **API Key for Format Access** field
3.  Save and clear all caches

The header link will automatically reappear and endpoints will be publicly
accessible.
