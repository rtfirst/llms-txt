<?php

declare(strict_types=1);

namespace RTfirst\LlmsTxt\Utility;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Path of the llms.txt of a site language.
 *
 * Every language has its own llms.txt below its base, e.g. /llms.txt for the
 * base "/" and /en/llms.txt for the base "/en/". A language with its own
 * domain has /llms.txt on that domain.
 */
final class LlmsTxtPath
{
    public const FILE_NAME = 'llms.txt';

    public static function forLanguage(?SiteLanguage $language): string
    {
        $basePath = $language instanceof SiteLanguage ? rtrim($language->getBase()->getPath(), '/') : '';

        return $basePath . '/' . self::FILE_NAME;
    }
}
