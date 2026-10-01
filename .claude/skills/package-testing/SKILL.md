---
name: package-testing
description: "How Aegis CSP is tested. Use when writing or changing anything in tests/ (Pest files, tests/TestCase.php, tests/Pest.php, tests/Fixtures), phpunit.xml.dist or stubs/nova (the Nova test double), when code or a test uses a Nova class or method not used before, when a test needs Nova, the core, a route, a stored row or the console, when a test fails only in the suite, or when PHPStan complains about a test."
license: MIT
---

# Testing the module

## The harness

- Pest 4 on Orchestra Testbench 10 (Laravel 12) with `laravel/nova` resolved to the test double in `stubs/nova` (see below) and the Aegis core from `../nova-aegis`. SQLite in memory, array cache and session (`phpunit.xml.dist`).
- `tests/TestCase.php` loads `Inertia\ServiceProvider`, `NovaCoreServiceProvider`, `AegisServiceProvider` and `CspServiceProvider`, runs the core's migrations, creates the `users` table, registers `AegisTool`, defines `viewAegis` as `is_admin`, and defines the routes the header tests call: `/page`, `/nova/page`, `/nova/data`, `/stream`, `/download`, `/existing` in the `web` group and `/outside-web` outside it.
- `tests/Pest.php` gives `admin()`, `editor()`, `sources(...)` (table rows) and `saveCsp([...])` (saves over the defaults through the core, like the settings page).
- `Unit/` holds the source and report URI rules and the `arch()` rules; `Feature/` the header, the settings, the console and the checks.

## The Nova test double (`stubs/nova`)

- `composer.json` declares `stubs/nova` as the `nova` path repository (`"versions": {"laravel/nova": "5.99.0"}`, `"symlink": true`), so `vendor/laravel/nova` links to it. No license, no `auth.json`, the same in CI. It is export-ignored; applications install the real Nova.
- It is a copy of the core's `stubs/nova`: our own minimal code with Nova's class names, public signatures and the behaviour the Aegis packages rely on, never Nova's code. This module relies on `NovaCoreServiceProvider` (the `nova` group containing `web`, the `nova.*` config), `Util::isNovaRequest()` and what the core uses (`Nova::tools()`, `Tool`, `Menu\MenuSection`).
- **The module uses a Nova API the double lacks:** add it to the core's `stubs/nova` first, with the real signature read in a Nova install, then copy the core's `stubs/nova` here unchanged and run `make ready` and, with a license, `make test.nova`.
- A test is about the module, not Nova: when a test only passes on one of them, rewrite it against the documented behaviour instead of deleting the coverage.
- `make test.nova` runs the suite on the real Nova in a throwaway copy (`docker/test-nova.sh`): it needs `auth.json` with your license; `NOVA_VERSION=5.9.3 make test.nova` pins a release.

## Rules

- Test what the browser or the administrator sees: the exact header a route answers, the validation error of a row, the dashboard line, the exit code of a command.
- Save through `saveCsp()` or the API. Write a row by hand with `storeCsp()` only to test a stored row the rules would refuse.
- Every refused source is a dataset row in `Unit/SourceTest.php` **and**, for injection, a refused save in `Feature/SettingsTest.php`.
- No test reaches the network. To make the settings unreadable, register a module under the `csp` key whose `defaults()` throws.
- Coverage stays at 90 % or more (`make test.coverage`).

## PHPStan max on tests, without ignores

- Use the global `Pest\Laravel\*` functions (`get`, `getJson`, `putJson`, `actingAs`), never `$this->` in a closure.
- `->not->` is not typed: assert on `preg_match(...)`, `has()` or `toBeFalse()` instead.
- Annotate dataset arrays (`/** @var array<string, mixed> $values */`).
- Console: `expect(Artisan::call('aegis:csp:disable'))->toBe(0)` and `Artisan::output()`.

## Workflow

1. Write the change and its tests; iterate with `docker compose run --rm php vendor/bin/pest --filter='...'`.
2. `make test.coverage` for gaps; cover the uncovered decisions, not getters.
3. `make ready` before the commit; `make test.nova` too when the change touches Nova and you have a license.
