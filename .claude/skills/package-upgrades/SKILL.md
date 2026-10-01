---
name: package-upgrades
description: "How a change reaches the Laravel applications that already run Aegis CSP. Use before changing a stored setting (its key, type or meaning), a default policy, the Nova allowances, the section key, what is memoized, composer.json constraints (the core or Laravel), the use of a core API, or when preparing a release or a tag."
license: MIT
---

# Upgrading installed applications

Applications update the module and the Aegis core independently with Composer. Every change is written for an application that has been running the previous version for months, with any core release of the same major.

## Versions and releases

- Semantic versions: a fix is a patch, a new setting or check a minor, a removed setting, a stricter default or a changed key a major.
- Every change adds a line under *Unreleased* in `CHANGELOG.md` saying what changes for the developer. A release moves them under the version and date.
- Release: merge the pull request, then `git tag -a v1.0.1 -m "..." && git push origin v1.0.1`. Packagist reads the tag.

## Constraints

- `laravel/framework` and `laravel/nova` cover whole majors; supporting a new major is a minor release with both ranges and tests against both.
- `wobqqq/nova-aegis` is `^1.0 || dev-main`. Raise the minimum only when the module needs a core API released later, and say so in the changelog.
- The `path` repository to `../nova-aegis` is for development; the lock file is development-only (export-ignored). Once the core is on Packagist, drop the path repository and `dev-main`.

## Stored settings

The section is the `csp` row of the core's `aegis_settings` table. The core merges the stored values over `defaults()` and drops keys the defaults no longer name, so adding a setting needs no migration.

- Never rename the section key or a setting key (`enabled`, `report_only`, `report_uri`, `apply_to`, `site_*`, `nova_*`, the `source` column).
- Changing a setting's type or meaning: use a **new key**. `CspSettings::fromArray()` keeps reading the old shape.
- A stored row may predate any rule: the read side drops what the rules would refuse, it never throws.

## Defaults and allowances

- A changed default changes the policy of every application that never saved the section. Never make a default looser; a stricter one is a major release with a changelog line.
- `NovaAllowances` only grows when Nova itself needs it. Removing an entry can lock administrators out of Nova.

## What is memoized

- `CspService` holds the re-read settings and one header per scope for the process; the core caches the stored values. A change to `CspSettings` or `Header` needs nothing more, since neither is written to a cache store.

## The core's contract

- Only use the core's public API (listed in its AGENTS.md). A newer API is used behind `method_exists` / `class_exists` with a fallback.
- Run this suite against the core's `main` before a core release, and against the lowest supported core before a module release.
