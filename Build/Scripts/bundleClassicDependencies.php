<?php

declare(strict_types=1);

/*
 * Bundles the Composer packages the extension needs at runtime for TYPO3 classic mode
 * (installations without Composer, e.g. from the TER).
 *
 * Every package in extra.typo3/cms.Package.providesPackages of composer.json is installed,
 * with its version constraint from "require", into the vendor directory configured there:
 * - TYPO3 v14 includes <vendor directory>/autoload.php in classic mode (Feature #108345)
 * - TYPO3 v13 loads the classes via the "autoload" section of ext_emconf.php
 *
 * Usage: php Build/Scripts/bundleClassicDependencies.php
 * Runs in the TER publish workflow before tailor packages the extension; the vendor
 * directories are ignored by git. Check the result with Build/Scripts/checkClassicArtefact.php.
 */

$rootDir = \dirname(__DIR__, 2);
$composerJson = json_decode((string)file_get_contents($rootDir . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$providedPackages = $composerJson['extra']['typo3/cms']['Package']['providesPackages'] ?? [];

$fail = static function (string $message): never {
    fwrite(STDERR, $message . "\n");
    exit(1);
};

$removeDirectory = static function (string $directory): void {
    if (!is_dir($directory)) {
        return;
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $file) {
        $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($directory);
};

$packagesByVendorDir = [];
foreach ($providedPackages as $package => $vendorDir) {
    $constraint = $composerJson['require'][$package] ?? null;
    if ($constraint === null) {
        $fail(\sprintf('"%s" is listed in providesPackages but not in require of composer.json.', $package));
    }
    if (trim((string)$vendorDir, '/') === '') {
        $fail(\sprintf('"%s" has no vendor directory in providesPackages of composer.json.', $package));
    }
    $packagesByVendorDir[trim((string)$vendorDir, '/')][$package] = $constraint;
}

// Resolve the packages for the lowest PHP version the extension supports (first version in require.php)
$platformConfig = [];
if (preg_match('/\d+\.\d+/', $composerJson['require']['php'] ?? '', $matches) === 1) {
    $platformConfig = ['platform' => ['php' => $matches[0] . '.0']];
}

foreach ($packagesByVendorDir as $vendorDir => $packages) {
    $vendorPath = $rootDir . '/' . $vendorDir;
    // The temporary composer.json is placed next to the vendor directory, so the generated
    // autoloader only contains paths relative to itself
    $workingDir = \dirname($vendorPath);
    if (is_file($workingDir . '/composer.json')) {
        $fail(\sprintf('%s/composer.json already exists, choose another vendor directory than "%s".', $workingDir, $vendorDir));
    }
    if (!is_dir($workingDir) && !mkdir($workingDir, 0o775, true)) {
        $fail(\sprintf('Could not create %s.', $workingDir));
    }

    echo \sprintf("=== Installing %s into %s\n", implode(', ', array_keys($packages)), $vendorDir);
    $removeDirectory($vendorPath);
    file_put_contents($workingDir . '/composer.json', json_encode([
        'description' => 'Temporary manifest of Build/Scripts/bundleClassicDependencies.php',
        'require' => $packages,
        'config' => [
            'vendor-dir' => basename($vendorPath),
            'optimize-autoloader' => true,
            'classmap-authoritative' => true,
            // TYPO3 checks the PHP version, a failing platform check must not break the frontend
            'platform-check' => false,
        ] + $platformConfig,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

    passthru(\sprintf(
        'composer update --working-dir=%s --no-dev --no-interaction --no-progress --no-plugins --no-scripts',
        escapeshellarg($workingDir),
    ), $exitCode);
    @unlink($workingDir . '/composer.json');
    @unlink($workingDir . '/composer.lock');
    if ($exitCode !== 0) {
        $fail(\sprintf('composer update failed for %s.', $vendorDir));
    }
}
