# Contributing

Lint, format, PHPStan, and PHPUnit cover **custom hotel-booking PHP only**, not Joomla core.

The toolchain is an isolated Composer project in [`tools/`](../tools/composer.json) so packages never land in `libraries/vendor/`.

## What is formatted

php-cs-fixer (`@PER-CS2.0`) runs on extension `src/` and `services/` directories listed in [`tools/.php-cs-fixer.php`](../tools/.php-cs-fixer.php).

Do **not** auto-format:

- `tmpl/` views (often tab-indented, Joomla style)
- Template `html/` overrides
- Anything under `libraries/`, `administrator/components/com_*` except `com_hotelbooking`

## Commands

From the project root:

```bash
ddev exec composer install --working-dir=tools
ddev exec composer cs-fix --working-dir=tools
ddev exec composer cs-check --working-dir=tools
ddev exec composer phpstan --working-dir=tools
ddev exec composer test --working-dir=tools
```

`cs-check`, `phpstan`, and `test:coverage` are what CI runs.

## Coverage

```bash
ddev exec composer test:coverage --working-dir=tools
```

CI uses **PCOV** and points `pcov.directory` at the repo root (coverage would otherwise stay at 0% if PHPUnit ran from `tools/`). Helper coverage must stay at **80%** or higher (`tools/check-coverage.php`).

Locally you need a coverage driver. With DDEV:

```bash
ddev xdebug on
ddev exec bash -lc 'export XDEBUG_MODE=coverage; composer test:coverage --working-dir=tools'
ddev xdebug off
```

HTML output is written to `build/coverage/html` (gitignored).

## PHPStan

Level 5, scoped to the same extension trees. Existing Joomla CMS-magic noise is listed in [`tools/phpstan-baseline.neon`](../tools/phpstan-baseline.neon).

Regenerate the baseline **only** when you intentionally accept remaining errors:

```bash
ddev exec composer phpstan-baseline --working-dir=tools
```

New code should not grow the baseline without a reason.

## CI

[`.github/workflows/php.yml`](../.github/workflows/php.yml) runs on:

- Every **pull request** (once)
- **Push** to `main` only

That avoids a double run (`push` + `pull_request`) on feature branches.
