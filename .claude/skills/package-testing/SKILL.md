---
name: package-testing
description: "How Aegis CSP is tested. Use when writing or changing anything in tests/ (Pest files, tests/TestCase.php, tests/Pest.php, tests/Fixtures), phpunit.xml.dist, when a test needs Nova, the core, a route, a stored row or the console, when a test fails only in the suite, or when PHPStan complains about a test."
license: MIT
---

# Testing the module

## The harness

- Pest 4 on Orchestra Testbench 10 (Laravel 12) with the real `laravel/nova` from nova.laravel.com and the Aegis core from `../nova-aegis`. SQLite in memory, array cache and session (`phpunit.xml.dist`).
- `tests/TestCase.php` loads `Inertia\ServiceProvider`, `NovaCoreServiceProvider`, `AegisServiceProvider` and `CspServiceProvider`, runs the core's migrations, creates the `users` table, registers `AegisTool`, defines `viewAegis` as `is_admin`, and defines the routes the header tests call: `/page`, `/nova/page`, `/nova/data`, `/stream`, `/download`, `/existing` in the `web` group and `/outside-web` outside it.
- `tests/Pest.php` gives `admin()`, `editor()`, `sources(...)` (table rows) and `saveCsp([...])` (saves over the defaults through the core, like the settings page).
- `Unit/` holds the source and report URI rules and the `arch()` rules; `Feature/` the header, the settings, the console and the checks.

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
3. `make ready` before the commit.
