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

CI and DDEV both use **PCOV**. The runner enables it only for that command (`pcov.enabled=1`) and points `pcov.directory` at the repo root (coverage would otherwise stay at 0% if PHPUnit ran from `tools/`). Helper coverage must stay at **80%** or higher (`tools/check-coverage.php`).

DDEV installs `php-pcov` via `webimage_extra_packages` in [`.ddev/config.yaml`](../.ddev/config.yaml). After changing that list, run `ddev restart`.

If PCOV is missing, you can use Xdebug instead:

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

## Manual DDEV checks

CI has no MySQL, so these stay local after `ddev exec php scripts/seed-platform-labs.php`:

- Associations tab on a destination/room/FAQ, then the site language switcher
- Schema tab on destination/room edit; view source should show one `application/ld+json` block from the system plugin
- Content → Fields → Star rating on a destination, visible on the site destination page
- Users → Privacy → Requests: New export/remove for a booking guest email (e.g. `jane@example.com`). Confirm the Mailpit link (booking guests are not CMS users). Export XML includes `hotelbooking_bookings`; Remove anonymises the guest and does not delete the row. Pending requests have no download/delete buttons.
- System → Mail Templates: Options must be HTML or Both. Edit `com_hotelbooking.partner_notify` (HTML Body is the Joomla mail template, not PHP), then Notify hotel on a Paris/Tokyo booking and check Mailpit
- `ddev exec php cli/joomla.php finder:index` then open **Search** in the site menu and look up a destination or room name. Components → Smart Search → Search Filters should list **Hotel Booking** (Destination and Room types). Re-run `seed-platform-labs.php` after indexing if that filter still shows 0 maps.
- Two hotel-manager users scoped to different destinations (`paris_manager` sees only Paris; Bookings open without a 403; FAQs stay Super User only; Home Dashboard does not list site-wide articles)

