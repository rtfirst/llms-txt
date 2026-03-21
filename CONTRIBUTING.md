# Contributing to LLMs.txt for TYPO3

Thank you for considering a contribution! This guide explains how to get started.

## Branching Model

1. Fork the repository on GitHub.
2. Create a feature branch **from `develop`**: `git checkout -b feature/my-change develop`
3. Open a pull request **targeting `develop`**.
4. The `main` branch is protected and only updated via reviewed PRs from `develop`.

## Development Setup

### Option A: DDEV (recommended)

```bash
git clone https://github.com/<your-fork>/llms-txt.git
cd llms-txt
ddev start
ddev composer install
```

### Option B: Standalone Composer

```bash
git clone https://github.com/<your-fork>/llms-txt.git
cd llms-txt
composer install
```

## Running Tests

```bash
vendor/bin/phpunit --configuration=phpunit.xml
```

## Code Quality

All checks must pass before a PR can be merged.

### PHPStan (level 8)

```bash
vendor/bin/phpstan analyse --configuration=phpstan.neon
```

### PHP-CS-Fixer

```bash
# Check only
vendor/bin/php-cs-fixer fix --dry-run

# Auto-fix
vendor/bin/php-cs-fixer fix
```

### Rector

```bash
# Check only
vendor/bin/rector process --dry-run --config=rector.php

# Auto-fix
vendor/bin/rector process --config=rector.php
```

## Coding Standards

- PSR-12 coding style (enforced via PHP-CS-Fixer `@PER-CS` rule set)
- `declare(strict_types=1);` in every PHP file
- Full type hints on all parameters and return types
- PHPStan level 8 with zero errors
- PHPUnit 10+ attributes (`#[Test]`, `#[DataProvider]`)

## Pull Request Checklist

Before submitting your PR, please confirm:

- [ ] All unit tests pass (`vendor/bin/phpunit --configuration=phpunit.xml`)
- [ ] PHPStan reports no errors (`vendor/bin/phpstan analyse --configuration=phpstan.neon`)
- [ ] PHP-CS-Fixer reports no issues (`vendor/bin/php-cs-fixer fix --dry-run`)
- [ ] Rector reports no issues (`vendor/bin/rector process --dry-run --config=rector.php`)
- [ ] New features include tests
- [ ] Commit messages follow the format: `[TYPE] Short description` (e.g., `[BUGFIX] Fix cache key collision`)

## Commit Message Prefixes

| Prefix       | Usage                        |
|--------------|------------------------------|
| `[FEATURE]`  | New functionality            |
| `[BUGFIX]`   | Bug fix                      |
| `[TASK]`     | Maintenance, refactoring     |
| `[DOCS]`     | Documentation only           |
| `[RELEASE]`  | Version release              |

## Questions?

Open a [discussion](https://github.com/rtfirst/llms-txt/issues) or reach out via the issue tracker.
