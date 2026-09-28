<?php

declare(strict_types=1);

/*
 * Checks an extracted TER artefact of the extension for TYPO3 classic mode (installations
 * without Composer): every Composer package needed at runtime must be bundled and loadable.
 *
 * Usage: php Build/Scripts/checkClassicArtefact.php <extracted artefact directory> <v13|v14>
 *
 * The class loading is set up like TYPO3 does it in classic mode:
 * - v13: the "autoload" section of ext_emconf.php, which replaces the one of composer.json
 * - v14: the "autoload" section of composer.json plus the autoload.php of every vendor
 *   directory in extra.typo3/cms.Package.providesPackages (Feature #108345). This is the
 *   class loading of TYPO3 v15 and of v14 as soon as ext_emconf.php is not evaluated anymore;
 *   until then v14 additionally uses the ext_emconf.php autoload section (see v13).
 *
 * Run each mode in its own PHP process, the registered autoloaders cannot be removed.
 */

use League\HTMLToMarkdown\HtmlConverter;
use RTfirst\LlmsTxt\Service\MarkdownRendererService;

[, $artefactDir, $mode] = $argv + [null, null, null];
if (!\is_string($artefactDir) || !is_dir($artefactDir) || !\in_array($mode, ['v13', 'v14'], true)) {
    fwrite(STDERR, "Usage: php Build/Scripts/checkClassicArtefact.php <extracted artefact directory> <v13|v14>\n");
    exit(2);
}
$artefactDir = rtrim((string)realpath($artefactDir), '/');

$failures = 0;
$check = static function (bool $condition, string $message) use (&$failures): void {
    echo ($condition ? '[OK]   ' : '[FAIL] ') . $message . "\n";
    if (!$condition) {
        $failures++;
    }
};

/**
 * @param array<string, string|list<string>> $psr4
 */
$registerPsr4 = static function (array $psr4) use ($artefactDir): void {
    spl_autoload_register(static function (string $className) use ($psr4, $artefactDir): void {
        foreach ($psr4 as $prefix => $paths) {
            if (!str_starts_with($className, $prefix)) {
                continue;
            }
            foreach ((array)$paths as $path) {
                $file = $artefactDir . '/' . trim($path, '/') . '/'
                    . str_replace('\\', '/', substr($className, \strlen($prefix))) . '.php';
                if (is_file($file)) {
                    require $file;
                    return;
                }
            }
        }
    });
};

$composerJson = json_decode((string)file_get_contents($artefactDir . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$providedPackages = $composerJson['extra']['typo3/cms']['Package']['providesPackages'] ?? [];

echo "=== Bundled Composer packages\n";
foreach (array_keys($composerJson['require'] ?? []) as $package) {
    // PHP, PHP extensions and the TYPO3 Core are available in every classic mode installation
    if ($package === 'php' || str_starts_with($package, 'ext-') || str_starts_with($package, 'typo3/cms-')) {
        continue;
    }
    $check(
        isset($providedPackages[$package]),
        \sprintf('"%s" (require) is listed in extra.typo3/cms.Package.providesPackages', $package),
    );
}
foreach ($providedPackages as $package => $vendorDir) {
    $vendorPath = $artefactDir . '/' . trim((string)$vendorDir, '/');
    $autoloadFile = $vendorPath . '/autoload.php';
    $check(
        is_file($autoloadFile) && str_contains((string)file_get_contents($autoloadFile), 'return ComposerAutoloaderInit'),
        \sprintf('"%s": Composer autoloader %s/autoload.php exists', $package, $vendorDir),
    );
    $installed = is_file($vendorPath . '/composer/installed.php') ? require $vendorPath . '/composer/installed.php' : [];
    $check(
        isset($installed['versions'][$package]['pretty_version']),
        \sprintf('"%s" is installed (%s)', $package, $installed['versions'][$package]['pretty_version'] ?? 'missing'),
    );
    $check(
        glob($vendorPath . '/' . $package . '/LICENSE*') !== [],
        \sprintf('"%s": license file is shipped', $package),
    );
}

echo "=== Class loading like TYPO3 {$mode} classic mode\n";
if ($mode === 'v13') {
    $_EXTKEY = $composerJson['extra']['typo3/cms']['extension-key'];
    $EM_CONF = [];
    require $artefactDir . '/ext_emconf.php';
    $autoload = $EM_CONF[$_EXTKEY]['autoload'] ?? $composerJson['autoload'] ?? [];
    $registerPsr4($autoload['psr-4'] ?? []);
} else {
    $registerPsr4($composerJson['autoload']['psr-4'] ?? []);
    foreach ($providedPackages as $vendorDir) {
        $autoloadFile = $artefactDir . '/' . trim((string)$vendorDir, '/') . '/autoload.php';
        if (is_file($autoloadFile)) {
            require $autoloadFile;
        }
    }
}

$check(class_exists(HtmlConverter::class), 'League\HTMLToMarkdown\HtmlConverter can be loaded');
try {
    $markdown = (new MarkdownRendererService())->render(
        '<html><head><title>Classic</title></head><body><main><h2>Classic mode</h2><p><strong>Bundled</strong> dependencies</p></main></body></html>',
        1,
        null,
        'https://example.org',
    );
    $check(
        str_contains($markdown, '## Classic mode') && str_contains($markdown, '**Bundled** dependencies'),
        'MarkdownRendererService renders Markdown',
    );
} catch (Throwable $e) {
    $check(false, 'MarkdownRendererService renders Markdown: ' . $e::class . ': ' . $e->getMessage());
}

echo $failures === 0 ? "Classic mode artefact check ({$mode}) passed\n" : "Classic mode artefact check ({$mode}) FAILED: {$failures} failure(s)\n";
exit($failures === 0 ? 0 : 1);
