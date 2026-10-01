## What changes

<!-- What the change does and why. -->

## Upgrading applications

<!-- A changed setting or default? A new requirement on the Aegis core? Anything a developer has to do after `composer update`? Write "none" if nothing. -->

## Checklist

- [ ] `make ready` passes (fixers, static analysis, tests with coverage)
- [ ] Every new or changed setting is validated in `CspModule::rules()` and again in `CspSettings::fromArray()`
- [ ] The Nova policy still keeps what Nova needs (`NovaAllowances`)
- [ ] CHANGELOG.md and README updated if the behaviour or the settings changed
