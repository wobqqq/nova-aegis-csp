# Aegis CSP

[![CI](https://github.com/wobqqq/nova-aegis-csp/actions/workflows/ci.yml/badge.svg)](https://github.com/wobqqq/nova-aegis-csp/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/wobqqq/nova-aegis-csp)](https://packagist.org/packages/wobqqq/nova-aegis-csp)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](https://github.com/wobqqq/nova-aegis-csp/blob/main/composer.json)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://github.com/wobqqq/nova-aegis-csp/blob/main/phpstan.neon.dist)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/wobqqq/nova-aegis-csp/blob/main/LICENSE.md)

**Aegis CSP** sends a `Content-Security-Policy` header that tells browsers which scripts, styles, images, fonts and frames your pages may load, so an injected script has nowhere to run from. You edit the policy from Nova, for the site and for the Nova panel separately.

It is a module of [Aegis](https://github.com/wobqqq/nova-aegis), the security suite for Laravel Nova: its settings appear in **Aegis → Settings**, its status on the Aegis dashboard.

## 🚀 Features

- **Per-directive source lists** for `default-src`, `script-src`, `style-src`, `img-src`, `font-src`, `connect-src`, `media-src`, `frame-src`, `object-src`, `base-uri`, `form-action` and `frame-ancestors`, one source per row.
- **Two policies**: one for the site, one for Nova, and a choice of where to send it: the site, Nova or both.
- **Report-only mode** (`Content-Security-Policy-Report-Only`) to try a policy before enforcing it, and an optional `report-uri`.
- **Strict validation**: every row must be one source expression (`'self'`, `'unsafe-inline'`, a `'sha256-…'` hash, `https:`, `data:`, `https://cdn.example.com`, `*.example.com`…). `;`, `,`, spaces, line breaks, control characters, stray quotes, unquoted keywords and nonces are refused when you save and dropped again when the header is built, so no value can add a directive or split the header.
- **No lock-out**: the Nova policy always keeps what Nova needs to run, so the page that turns the policy off keeps working.
- **Every response**: pages, JSON, streamed and file downloads of the `web` middleware group get the header; it replaces a policy the response already has.
- **Cheap**: the policy is built once per process from the cached Aegis settings, no database query per request.
- **Dashboard**: a line on the Aegis overview, and the **Content Security Policy strength** check, which warns when the site's policy still lets an injected script run (`'unsafe-inline'`, `'unsafe-eval'`, `https:`, `*`…) or lacks `object-src 'none'`, `base-uri` or `frame-ancestors`.

## 📦 Requirements

- PHP 8.2 or higher
- Laravel 12
- Laravel Nova 5
- [Aegis](https://github.com/wobqqq/nova-aegis) 1.1 or higher (`wobqqq/nova-aegis`), installed and its tool registered

## 📥 Installation

### 1. Install the package

```bash
composer require wobqqq/nova-aegis-csp
```

The service provider is discovered automatically.

### 2. Run the migrations

```bash
php artisan migrate
```

This creates the Aegis settings table if the core is new to the application; the module adds no table of its own.

### 3. Set up Aegis (once per application)

If Aegis is new to the application, register its tool and define the `viewAegis` gate as the [Aegis README](https://github.com/wobqqq/nova-aegis#-installation) describes. Skip this step if you already use another Aegis module.

### 4. Turn it on in Nova

Open **Aegis → Settings → Content Security Policy** in Nova, check the directives, choose where the policy is sent (the site, Nova or both), switch **Send the Content-Security-Policy header** on and save. Watch the browser console for blocked resources.

## ⚙️ Configuration

Everything is set on the settings page; there is no config file.

| Setting | Default | |
|---|---|---|
| Send the Content-Security-Policy header | off | |
| Send it to | the site | the site, Nova, or both |
| Report only | off | sends `Content-Security-Policy-Report-Only` instead |
| Report URI | empty | an `http(s)` URL or a path on the site, such as `/csp-report` |
| Site directives | see below | an empty directive is not sent |
| Nova directives | see below | Nova's required sources are always kept |

The default site policy keeps a typical site working:

```
default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: blob: https:; font-src 'self' data: https:; connect-src 'self' https:; media-src 'self' blob: https:; frame-src 'self' https:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'
```

The default Nova policy:

```
default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self'; media-src 'self' blob:; frame-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'
```

Nova cannot run without `'self'`, `'unsafe-inline'` and `'unsafe-eval'` in `script-src` (its boot script is inline and tools compile their templates), `'self'` and `'unsafe-inline'` in `style-src`, `'self'` and `data:` in `img-src`, and `'self'` in `font-src`, `connect-src` and `form-action`. The settings page refuses a Nova policy without them, or with a source that cancels them (`'none'`, a hash or `'strict-dynamic'`, which make browsers ignore `'unsafe-inline'`).

## 🧯 Recovery commands

If the policy breaks your pages or locks you out of Nova:

```bash
php artisan aegis:csp:disable          # stop sending the policy
php artisan aegis:csp:disable --nova   # stop sending it to Nova only, keep it on the site
```

Both keep your directives, so you can switch the policy back on once it is fixed.

## ⚠️ Good to know

- **Tighten the defaults.** They allow `'unsafe-inline'` and any `https:` source so that switching the module on does not break a site. Replace `https:` with the hosts you use, use hashes instead of `'unsafe-inline'` where you can, and watch the browser console for blocked resources, ideally in report-only mode first.
- **No nonces.** The header is the same for every response, and a nonce that never changes protects nothing; use hashes for inline scripts and styles.
- **Alpine.js and Livewire** need `'unsafe-eval'` in the site's `script-src`, unless you use Alpine's CSP build.
- **One place in charge.** The module replaces a `Content-Security-Policy` the response already has, but the web server, a CDN or a proxy in front of the application may add or override its own. Keep the header in one place.
- **Only the `web` middleware group** gets the header: routes outside it (an `api` group, a route without middleware) and responses served by the web server itself (static files) do not.
- **Nova requests** are recognised the way Nova does it: the Nova path, `nova-api/*`, `nova-vendor/*`, or Nova's own domain when `nova.domain` is set. A Nova behind a proxy that rewrites the path must keep `nova.path` matching what the application receives.
- **Proxies and spoofable headers.** The policy does not depend on the client's IP or on `X-Forwarded-*` headers, so trusted proxies change nothing here; behind a TLS-terminating proxy, configure `TrustProxies` anyway so that `'self'` and an `https:` report URI match the scheme browsers see.
- **Report URI.** Browsers post reports there as JSON, unauthenticated; the endpoint is yours to write, and should be rate-limited.

## 🔒 Security

Please report a vulnerability privately, as described in [SECURITY.md](https://github.com/wobqqq/nova-aegis-csp/blob/main/SECURITY.md).

## 🛠️ Development

The toolchain runs in Docker, the host needs nothing but `docker` and `make`. Until the core is on Packagist, Composer installs it from a sibling checkout: clone [nova-aegis](https://github.com/wobqqq/nova-aegis) next to this repository (`../nova-aegis`); `docker-compose.yaml` mounts it into the container. No Nova license is needed: development and CI run on a test double of Nova in `stubs/nova` (installed as `laravel/nova` from a path repository, never shipped). Applications still install the real Nova.

```bash
make install        # composer install
make code.fix       # composer normalize, Rector, PHP CS Fixer
make code.check     # composer validate/audit, php -l, PHP CS Fixer, Rector, PHPStan (level max)
make test.coverage  # Pest with coverage (90 % minimum)
make ready          # everything above
make test.nova      # optional: the suite on the real Nova
```

`make test.nova` copies the repository to a temporary directory, installs the real `laravel/nova` from nova.laravel.com there and runs Pest; it needs your own Nova license in `auth.json` (gitignored), and `NOVA_VERSION=5.9.3 make test.nova` picks a release your license may download. The working copy, its `vendor/` and `composer.lock` are left untouched.
