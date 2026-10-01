---
name: aegis-security
description: "Security checklist for the Aegis CSP module. Use for any change to what a stored or submitted value can put in a response header: a source rule (src/Policy/Source.php, ReportUri), a validation rule in src/Validation or CspModule::rules(), CspSettings::fromArray() or header(), the Nova allowances, the middleware, the default policies, the recovery command, caching of the policy, or a security review of this module."
license: MIT
---

# Aegis CSP security checklist

The module writes a response header from values an administrator typed. Treat every rule below as a test to write, not a guideline to remember.

## 1. The header: one token per source

- A source reaches the header only through `Source::isValid()`: a quoted keyword from the fixed list, a `'sha256|384|512-…'` hash, a scheme (`https:`), or a host with an optional scheme, port and path.
- Refused, with a test for each: `;`, `,`, any whitespace, CR/LF and other control characters, non-ASCII, a quote anywhere but around a keyword, an unquoted keyword (`self` is a host to a browser), a nonce (a fixed nonce protects nothing), more than 255 characters.
- Patterns end with `\z` and the `D` modifier: `$` would accept a trailing newline.
- Directives are joined with `"; "`, sources with one space; the report URI is appended as `report-uri <uri>` only after `ReportUri::isValid()`.
- A list is capped at `CspModule::MAX_SOURCES`, repeats are dropped, a `'none'` next to other sources is dropped (browsers ignore it there).

## 2. Validate every setting twice

- On save: `CspModule::rules()` (`boolean`, `Rule::enum(Target::class)`, `present|array|max:`, `array:source`, `ValidSource`, `ValidSourceList`, `ValidReportUri`). The core drops keys the defaults do not name.
- On read: `CspSettings::fromArray()` re-checks every value through `Values`, `Source` and `ReportUri`, falls back to a safe value (off, the site only, no report URI), and never throws on a hand-written row.
- A new setting gets both, and a test with a stored row the rules would refuse.

## 3. No lock-out

- The Nova policy keeps what Nova needs (`NovaAllowances`): refused on save by `ValidSourceList`, restored on read by `apply()`. A source that cancels a required one (`'none'`, a hash or `'strict-dynamic'` next to `'unsafe-inline'`) counts as missing.
- The module ships off and sends only to the site until the administrator chooses Nova.
- `php artisan aegis:csp:disable [--nova]` works whatever is stored: it saves the re-read settings, never the raw row.

## 4. Every request is cheap and safe

- The middleware asks `CspService::header()`, which memoizes one header per scope from `Aegis::settings()` (cached by the core). No query, no network, no logging per request; the arch tests and the "no database row" test pin it.
- The memo is forgotten on `SettingsSaved` for `csp`: a saved policy applies at once.
- A failure is `report()`ed and the response goes out without the header, never as a 500.
- The header replaces one the response already has; only the `web` group is covered.

## 5. Output and secrets

- Labels and messages come from `resources/lang`; the core's page prints them as text.
- Never log a stored value, a header, a request, `.env` or `auth.json`.

## Review procedure

1. `git diff --stat` and list every changed rule, pattern, default, allowance and the middleware.
2. Walk each through sections 1 to 5 and name the test that pins it.
3. Run `make ready`.
4. Report each finding as: file:line, what an attacker or a stale row sends, what the header becomes, the fix.
