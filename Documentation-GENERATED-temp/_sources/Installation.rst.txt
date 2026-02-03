:navigation-title: Installation

..  _installation:

============
Installation
============

..  _installation-composer:

Installation via Composer (Recommended)
=======================================

Install the extension via Composer:

..  code-block:: bash

   composer require rtfirst/llms-txt

Then activate the extension and clear the cache:

..  code-block:: bash

   vendor/bin/typo3 extension:setup
   vendor/bin/typo3 cache:flush

For DDEV environments:

..  code-block:: bash

   ddev composer require rtfirst/llms-txt
   ddev typo3 extension:setup
   ddev typo3 cache:flush

See also :ref:`Installing extensions <t3start:installing-extensions>` in the
TYPO3 Getting Started documentation.

..  _installation-classic:

Installation in Classic Mode
============================

1.  Download the extension from the
    `TYPO3 Extension Repository (TER) <https://extensions.typo3.org/extension/llms_txt>`__
    or from `GitHub <https://github.com/rtfirst/llms-txt>`__.

2.  Install the extension via the Extension Manager in the TYPO3 Backend.

3.  Clear all caches.

..  _installation-site-set:

Activate the Site Set
=====================

After installation, you need to add the Site Set to your site configuration:

1.  Go to **Site Management > Sites** in the TYPO3 Backend.

2.  Edit your site configuration.

3.  Go to the **Sets** tab.

4.  Add the set **LLMs.txt Generator** (``rtfirst/llms-txt``).

5.  Save and clear all caches.

..  figure:: /Images/SiteSet.png
   :alt: Adding the LLMs.txt Generator site set
   :class: with-shadow

   Add the LLMs.txt Generator site set to your site configuration.

..  _installation-verify:

Verify Installation
===================

After installation, verify that everything works:

1.  Access ``https://your-site.com/llms.txt`` - You should see the generated
    llms.txt content.

2.  Access any page with ``.md`` suffix, e.g., ``https://your-site.com/about.md``
    - You should see Markdown content with YAML frontmatter.

3.  Check the page properties of any page - You should see a new **LLM** tab.

..  tip::

   If you get a 404 error, make sure you've added the Site Set and cleared
   all caches.
