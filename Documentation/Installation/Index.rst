..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

Requirements
============

-  TYPO3 13.0 - 14.x
-  PHP 8.2 or higher

Installation via Composer
=========================

The recommended way to install this extension is via Composer:

..  code-block:: bash

    composer require rtfirst/llms-txt

Activate the Extension
======================

After installation, activate the extension and clear caches:

..  code-block:: bash

    # Activate extension
    vendor/bin/typo3 extension:setup

    # Clear all caches (this also generates the llms.txt files)
    vendor/bin/typo3 cache:flush

If you're using DDEV:

..  code-block:: bash

    ddev typo3 extension:setup
    ddev typo3 cache:flush

Add Site Set
============

To enable the extension's settings for your site:

1. Go to **Site Management > Sites**
2. Edit your site configuration
3. In the **Sets** tab, add "LLMs.txt Generator"
4. Save the configuration

Verification
============

After installation, verify that the extension works correctly:

1. Clear all caches
2. Check that ``public/llms.txt`` exists
3. Visit your website with ``?format=clean`` appended to any URL
4. Visit your website with ``?format=md`` appended to any URL

Example:

..  code-block:: text

    https://your-domain.com/?format=clean
    https://your-domain.com/?format=md
