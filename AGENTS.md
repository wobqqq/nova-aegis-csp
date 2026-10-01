# AGENTS.md

Guidance for coding agents working in this repository.

## What this is

**Aegis CSP** (`wobqqq/nova-aegis-csp`) is a module of the Aegis security suite for Laravel Nova (Laravel 12, PHP 8.2+). It sends a `Content-Security-Policy` header (or `Content-Security-Policy-Report-Only`) built from per-directive source lists the administrator edits in **Aegis → Settings → Content Security Policy**, to the site, to the Nova panel or to both, each with its own policy.

It requires the core package [`wobqqq/nova-aegis`](https://github.com/wobqqq/nova-aegis): the module registers its settings section, its dashboard line and a check through the core's public API, and the core stores, validates, caches and draws the settings.

This is a **security product installed on production applications**. A bug here breaks the pages of a site, locks administrators out of Nova or silently leaves the application without a policy. Security and safe upgrades come before everything else.

## The self-check gate (run before every commit)

Everything runs in Docker; the host needs no PHP. The core is required from the sibling checkout `../nova-aegis` through a Composer `path` repository until it is on Packagist; `docker-compose.yaml` mounts it at `/nova-aegis`, where the `vendor/wobqqq/nova-aegis` symlink resolves.

```bash
make install        # composer install
make code.fix       # composer normalize, Rector, PHP CS Fixer
make code.check     # validate --strict, normalize --dry-run, composer audit, php -l, cs, Rector, PHPStan max
make test           # Pest
make test.coverage  # Pest with pcov, failing below 90 %
make ready          # all of the above
```

`make ready` must pass. PHPStan runs at `level: max` with strict rules and **no baseline**: fix the type, never add an ignore. Advisories from `composer audit` are fixed by updating the package, never ignored.

Installing Nova needs a license: `auth.json` (gitignored and export-ignored) holds the credentials. Never read, print or commit it.

## How the code is laid out

| Path | Holds |
|------|-------|
| `src/CspServiceProvider.php` | Wiring only: the module and the check with `Aegis::module()` / `Aegis::check()`, the `SettingsSaved` listener, the middleware in the `web` group, the command. |
| `src/CspModule.php` | The `csp` section: defaults (the site and Nova policies), rules, fields, the dashboard line. |
| `src/CspService.php` | The settings read once per process (`settings()`), the header for a request (`header()`), `forget()`. |
| `src/Policy/` | `Source` (the one rule every source must match), `ReportUri`, `NovaAllowances` (what Nova cannot work without), `CspSettings` (the settings re-read and the header built), `Header`. |
| `src/Validation/` | The rules the settings form runs: `ValidSource` per row, `ValidSourceList` per directive, `ValidReportUri`. |
| `src/Http/Middleware/ContentSecurityPolicy.php` | Sets the header on every response of the `web` group. |
| `src/Checks/PolicyStrengthCheck.php` | Warns about a site policy that still lets an injected script run. |
| `src/Console/DisableCommand.php` | `aegis:csp:disable [--nova]`, the way back. |
| `resources/lang/en/csp.php` | Every label and message, under `aegis-csp::csp.*`. |

### How it uses the core (its contract)

The core and the module are updated independently, so the module only uses what the core lists as public API (see the core's AGENTS.md):

- `Aegis::module()`, `Aegis::check()`, `Aegis::settings('csp')`;
- the `Module` and `Check` interfaces, `CheckResult`, `Status`, `Field` (`toggle`, `select`, `text`, `table`) and `FieldType`;
- the `SettingsSaved` event, to forget the policy read in this process;
- the `nova-vendor/aegis` routes and the `viewAegis` gate, which guard the settings page (the module adds no route of its own);
- `Wobqqq\Aegis\Support\Values` to read stored values, and `SettingsRepository::save()` in the recovery command.

A newer core API is used only behind a check (`method_exists`, `class_exists`) with a fallback, so the module keeps working with every core release of the same major. The section key `csp` and the setting keys are public too: never rename them.

### The request path

1. `ContentSecurityPolicy` runs after the response is built, for every route in the `web` group (Nova's routes are in it too).
2. `CspService::header()` decides the scope with Nova's `Util::isNovaRequest()` (the Nova path, `nova-api/*`, `nova-vendor/*`, or Nova's own domain), then returns the memoized `Header` of that scope, or null when the policy is off, the target does not cover the scope or no directive is left.
3. The settings come from `Aegis::settings('csp')`, cached by the core: **no database query per request.** The memo is cleared on `SettingsSaved` for `csp`.
4. Any failure is reported and the response goes out without the header.

## Upgrading installed applications safely

Read the `package-upgrades` skill before changing anything that reaches an application that already runs the module. In short:

- A change to what is **stored** (a setting's key, type or meaning) keeps reading the old shape (`CspSettings::fromArray()` falls back on a bad value) or uses a new key.
- A change to a **default** changes the policy of every application that never saved the section: make it stricter only in a major release, never looser, and say so in the changelog.
- `NovaAllowances` only grows when Nova itself needs more; removing an entry can lock administrators out.
- Every change is a line under *Unreleased* in `CHANGELOG.md`.

## Security rules (always)

Read the `aegis-security` skill for the full checklist. For this module in particular:

- **A source is one CSP source expression.** `Source::isValid()` accepts the quoted keywords, hashes, schemes and hosts, and refuses `;`, `,`, whitespace, control characters, non-ASCII, stray quotes, unquoted keywords and nonces. It runs when the settings are saved (`ValidSource`) **and** when they are read (`CspSettings::fromArray()`), since a stored row may predate the rule. The report URI has the same two checks.
- **Directives are joined with `"; "`**, sources with one space, nothing else reaches the header.
- **The Nova policy cannot lock the administrators out**: `ValidSourceList` refuses a Nova directive without what Nova needs or with a source that cancels it (`'none'`, a hash or `'strict-dynamic'` next to `'unsafe-inline'`), and `NovaAllowances::apply()` restores them when the policy is read.
- **Defaults are safe**: the module ships off, sends only to the site, and the default policies keep a typical site and Nova working. Never make them looser.
- **The application keeps working when the module breaks**: a failing read never turns a request into a 500.
- **Every request path is cheap**: the middleware reads the memoized header, nothing else; no query, no network, no logging per request.
- The header replaces any policy the response already has; never log a header, a stored value or a request.

Recovery from the console:

- `php artisan aegis:csp:disable` — turns the policy off, for a site whose pages it broke.
- `php artisan aegis:csp:disable --nova` — stops sending it to Nova only, for administrators locked out of the panel.

## Tests

Pest 4 on Orchestra Testbench 10 with the real `laravel/nova` and the Aegis core (SQLite in memory, array cache). `tests/TestCase.php` boots Inertia, Nova, the core and the module, registers `AegisTool`, defines `viewAegis` and the routes the header tests call (site, Nova, streamed, file, outside the `web` group). No test reaches the network. Read the `package-testing` skill.

## Git workflow

- `main` is protected: **never push to it and never force-push.** Every change after the initial build goes through a pull request:
  1. branch off the latest `main`, named after the change (`fix/…`, `feat/…`, `chore/…`, `docs/…`);
  2. commit on the branch and `git push -u origin <branch>`;
  3. open a pull request with the template filled in (what changes, what it means for applications that upgrade);
  4. merge once `make ready` passed, then delete the branch.
- A release is a tag pushed on a merged commit of `main` (`git tag -a v1.0.0 -m "..." && git push origin v1.0.0`); Packagist reads the tag.
- Code, comments, commit messages, pull requests, issues and documentation are written in **English**.

## Conventions

- `declare(strict_types=1);` in every PHP file; PSR-12 via PHP CS Fixer.
- Code documents itself: names over comments. A comment explains a non-obvious *why*, in one sentence.
- Every class is `final`; value objects are `final readonly`.
- Laravel and core patterns: container bindings, `Aegis::*`, `ValidationRule` objects, `Values` for stored values.
- Commits: imperative subject saying what the change does for the application ("Refuse a nonce in a source list"), a body with the why.
