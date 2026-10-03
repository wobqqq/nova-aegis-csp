# CLAUDE.md

@AGENTS.md

## Skills and hooks

- Skills in `.claude/skills/`: the architecture skills (`application-layer`, `dependency-injection`, `error-handling`, `validation`, `events`, `testing-architecture`, `domain-layer-cqrs`, `package-boundaries`; see *Architecture* in AGENTS.md), `aegis-security` (read it for any change to a source rule, the header, the Nova allowances, a setting or the recovery command), `package-upgrades` (anything that reaches an installed application), `package-testing`, `nova-development`, `testing-best-practices`, `laravel-best-practices`.
- A changed PHP file is formatted by the `PostToolUse` hook in `.claude/settings.json`; still run `make ready` before you say a change is done, and report its result.
- The core lives in the sibling repository `../nova-aegis`; never change it from here. A change that needs a new core API is made in the core first, and used here behind a check.
- `laravel/nova` is the test double in `stubs/nova`, a copy of the core's: a new Nova API is added to the core's double first, then copied here unchanged (see `package-testing`).
- Never push to `main`: work on a branch and open a pull request (see *Git workflow* in AGENTS.md). Write everything in English.
