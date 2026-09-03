# Hotel Booking (Joomla)

A Joomla 6.1 learning site with a custom **Hotel Booking** component: destinations, rooms, guest bookings, FAQs, and offers. The front end is bilingual (English and Thai) and runs locally on [DDEV](https://ddev.com).

This repository is a full CMS tree plus the hotel-booking extensions. Upstream Joomla’s own readme is still [`README.txt`](README.txt).

## Quick start

You need Docker and DDEV. PHP 8.3 and MariaDB ship with the project config.

```bash
ddev start
```

- Site: https://joomla-hotel-booking.ddev.site
- Admin: https://joomla-hotel-booking.ddev.site/administrator/

This clone expects an already-installed local Joomla (see [local setup](docs/local-setup.md)). Super User credentials are not stored in git.

After the people-fields seed, you can log in on the **site** as `maya` / `ChangeMe123!` (not a Super User).

## What is in here

- Destinations and rooms with galleries, amenities, and nested offers
- Guest booking requests and partner/hotel notification helpers
- FAQs scoped to the site or a destination
- Article editor snippet picker that inserts `{hotelbooking}` tags (rendered by a content plugin)
- Hotel landing page modules (hero, details, rooms) driven by the destination in the URL
- City Guide template style with its own module positions
- Cookie banner and guest Contact-form consent rows in Privacy Consents
- Custom Fields demos for articles, contacts, and users

Details: [architecture](docs/architecture.md).

## Custom extensions

| Extension | Role |
|-----------|------|
| `com_hotelbooking` | Destinations, rooms, bookings, FAQs, snippet picker |
| `plg_content_hotelbooking` | Turns `{hotelbooking}` tags into cards |
| `plg_editors-xtd_hotelbooking` | Editor button that opens the snippet picker |
| `plg_system_hbconsent` | Guest Contact ticks and cookie Accept → Privacy Consents |
| `mod_hotelhero` | Hotel landing hero |
| `mod_hoteldetails` | Hotel landing intro, offers, CTA |
| `mod_hotelrooms` | Rooms for the current hotel landing destination |
| `tpl_hotelbooking` | Main site template (Cassiopeia child) |
| `tpl_cityguide` | City Guide layout (`city-*` module positions) |

## Seed scripts

Idempotent. Safe to re-run:

```bash
ddev exec php scripts/seed-article-fields.php
ddev exec php scripts/seed-people-fields.php
ddev exec php scripts/seed-privacy-consent.php
```

What each one creates is listed in [local setup](docs/local-setup.md).

## Lint, types, and tests

Tooling lives in `tools/` so it does not mix with Joomla’s `libraries/vendor/`.

```bash
ddev exec composer install --working-dir=tools
ddev exec composer cs-check --working-dir=tools
ddev exec composer phpstan --working-dir=tools
ddev exec composer test --working-dir=tools
```

PRs run the same checks in [GitHub Actions](.github/workflows/php.yml). How to format, regenerate the PHPStan baseline, and collect coverage: [contributing](docs/contributing.md).
