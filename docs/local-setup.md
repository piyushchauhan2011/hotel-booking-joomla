# Local setup

This repo is a **full Joomla 6.1 CMS tree** with hotel-booking extensions already in place. It is not a from-scratch installer zip. `configuration.php` is gitignored; a working clone assumes Joomla is already installed in this DDEV project.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/)
- [DDEV](https://ddev.com/get-started/)

PHP **8.3** and **MariaDB 11.8** come from [`.ddev/config.yaml`](../.ddev/config.yaml). You do not install them on the host.

## Start the site

```bash
ddev start
```

| Surface | URL |
|---------|-----|
| Site | https://joomla-hotel-booking.ddev.site |
| Administrator | https://joomla-hotel-booking.ddev.site/administrator/ |
| phpMyAdmin | `ddev phpmyadmin` |
| Mailpit | `ddev mailpit` |

Super User credentials are whatever you created at install time. They are not in this repository.

## Demo site user

[`scripts/seed-people-fields.php`](../scripts/seed-people-fields.php) creates a front-end user (not Super User):

- Username: `maya`
- Password: `ChangeMe123!`

Use that account for My Profile, Contact, and About demos.

## Seed scripts

Each script is idempotent (skips records that already exist). Run from the project root via DDEV:

```bash
ddev exec php scripts/seed-article-fields.php
ddev exec php scripts/seed-people-fields.php
ddev exec php scripts/seed-privacy-consent.php
```

| Script | Creates |
|--------|---------|
| `seed-article-fields.php` | Two field groups, four article fields, two Blog articles for the Custom Fields UI |
| `seed-people-fields.php` | Contact/User field groups, user `maya`, org/team contacts, About / Contact / My Profile menus |
| `seed-privacy-consent.php` | Privacy Policy article, hidden menu item, enables core Privacy Consent plus `plg_system_hbconsent`. Does **not** pre-consent `maya` (the lesson is the redirect after login) |

## Languages

Hotel-booking strings ship in **en-GB** and **th-TH** under the component and plugin `language/` folders. Switch language in Joomla (site or admin) to see translations. Destination and room **content** is whatever is stored in the database (some demo names may be mixed language).
