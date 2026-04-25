<?php

declare(strict_types=1);

$autoloadCandidates = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
    __DIR__ . '/../../../../../../vendor/autoload.php',
];

$autoloader = null;
foreach ($autoloadCandidates as $candidate) {
    if (file_exists($candidate)) {
        $autoloader = require $candidate;
        break;
    }
}

if ($autoloader === null) {
    fwrite(STDERR, "Composer autoloader not found.\n");
    exit(1);
}

$frameworkBootstrap = dirname((string)(new ReflectionClass(\TYPO3\TestingFramework\Core\Testbase::class))->getFileName(), 3)
    . '/Resources/Core/Build/FunctionalTestsBootstrap.php';

require $frameworkBootstrap;
