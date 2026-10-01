# Changelog

All notable changes are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [semantic versioning](https://semver.org/).

## [Unreleased]

### Added

- A **Content Security Policy** section in the Aegis settings: twelve directives for the site and twelve for Nova, edited as source lists, off until enabled.
- The policy is sent to the site, to Nova or to both, enforced or report-only, with an optional `report-uri`.
- Every source is validated as one CSP source expression when it is saved and again when the header is built; `;`, `,`, whitespace, control characters, stray quotes, unquoted keywords and nonces are refused.
- The Nova policy always keeps the sources the panel needs, so it cannot lock administrators out.
- The header is set on every response of the `web` group, streamed and file responses included, and replaces a policy the response already has.
- A dashboard line and the **Content Security Policy strength** check.
- `aegis:csp:disable [--nova]` console command.
