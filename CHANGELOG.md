# Changelog

All notable changes are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [semantic versioning](https://semver.org/).

## [Unreleased]

## [1.1.0] - 2026-10-01

### Added

- Laravel 13 support; CI runs the suite on Laravel 12 and 13.
- Works with Aegis 2 as well as Aegis 1.1 or later.

### Changed

- PHP 8.4 or later is required.
- Native types throughout: typed constants and properties, `#[\Override]` on every overriding method, readonly value objects; no behaviour change.

## [1.0.1] - 2026-10-01

### Changed

- Development and CI run on a test double of Nova (`stubs/nova`, not shipped) and need no Nova license; `make test.nova` runs the suite on the real Nova. Nothing changes for applications.
- The README splits the installation into numbered steps.
- The Dependabot config no longer reads the Nova registry.

## [1.0.0] - 2026-10-01

### Added

- A **Content Security Policy** section in the Aegis settings: twelve directives for the site and twelve for Nova, edited as source lists, off until enabled.
- The policy is sent to the site, to Nova or to both, enforced or report-only, with an optional `report-uri`.
- Every source is validated as one CSP source expression when it is saved and again when the header is built; `;`, `,`, whitespace, control characters, stray quotes, unquoted keywords and nonces are refused.
- The Nova policy always keeps the sources the panel needs, so it cannot lock administrators out.
- The header is set on every response of the `web` group, streamed and file responses included, and replaces a policy the response already has.
- A dashboard line and the **Content Security Policy strength** check.
- `aegis:csp:disable [--nova]` console command.
- Requires Aegis 1.1 or later (`wobqqq/nova-aegis` `^1.1`): the module uses only the core's public API, `Aegis::save()` included.

[Unreleased]: https://github.com/wobqqq/nova-aegis-csp/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/wobqqq/nova-aegis-csp/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/wobqqq/nova-aegis-csp/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/wobqqq/nova-aegis-csp/releases/tag/v1.0.0
