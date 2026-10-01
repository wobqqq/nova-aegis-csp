---
name: nova-development
description: >-
  Use whenever a change touches how the module meets Nova: the Nova policy and
  NovaAllowances, how a request is recognised as a Nova request
  (Util::isNovaRequest), the web middleware group, the settings fields drawn by
  the Aegis page (Field, FieldType), the viewAegis gate, or a new Nova release
  that loads scripts, styles, fonts or images differently. Use it with
  aegis-security for anything about the header.
metadata:
  author: project
---

# Nova and this module

The module has no Nova tool, page or Vue component of its own: the Aegis core draws its settings from `CspModule::fields()` and guards them with `viewAegis`. `vendor/laravel/nova` here is the test double in `stubs/nova`, not Nova: check a behaviour in a real Nova install before relying on it, and add any Nova API the module starts using to the double first (see `package-testing`).

## What Nova needs from a policy

Read `resources/views/layout.blade.php` and `public/` of a real Nova install when Nova is upgraded (the double has neither):

- an inline theme script and the inline `createNovaApp(config)` boot script: `script-src 'unsafe-inline'`;
- the Vue template compiler used by tools (`new Function` in `vendor.js`): `script-src 'unsafe-eval'`;
- inline styles and Tailwind's `data:` SVG backgrounds: `style-src 'unsafe-inline'`, `img-src data:`;
- its fonts, scripts and API on the same origin: `'self'` for `script-src`, `style-src`, `font-src`, `connect-src`, `form-action`;
- Gravatar avatars: the default `img-src` keeps `https:`.

The required part is `NovaAllowances::REQUIRED`; the rest is the editable default in `CspModule::NOVA`. A source that cancels `'unsafe-inline'` (a hash, `'strict-dynamic'`) or `'none'` counts as missing.

## Recognising a Nova request

- `CspService::header()` uses `Laravel\Nova\Util::isNovaRequest()`: the Nova path and its subpaths, `nova-api/*`, `nova-vendor/*`, or Nova's own domain when `nova.domain` is set. Use it, never a path of our own.
- Nova's routes are in its `nova` middleware group, which contains `web`, so the middleware pushed to `web` covers both.

## Settings fields

- One `Field::table()` per scope and directive with a single `source` column; `Field::select()` for `apply_to`, `Field::toggle()` and `Field::text()` for the rest.
- The core prefixes errors by field name, so a row error keyed `site_script_src.1.source` shows under its table.
- New strings go in `resources/lang/en/csp.php`.

## Checklist

- [ ] The Aegis page still works with the Nova policy on (`HeaderTest` "sends the Nova policy to the Aegis page itself").
- [ ] A Nova upgrade that needs a new source adds it to `NovaAllowances` and the Nova default together.
- [ ] `make ready` passes.
