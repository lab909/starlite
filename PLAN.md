# Starlite roadmap

Starlite is a starting point, not a full framework: SEO, Twig, Datastar, a Markdown blog, translations
and a deploy pipeline, with no database and no admin panel. A new site starts from this **skeleton**
(lab909/starlite, "Use this template") and installs the framework package (lab909/starlite-framework,
`starlite/framework`) with Composer; the docs are lab909/starlite-framework-docs. See CONTRIBUTING.md.

This roadmap lists what makes such sites easy to build and keep up to date. The first site will be
**Nocturne**, a calm ambient-sound mixer (claude.ai/design project "Nocturne Mockups"): a blog of
SEO articles around the app, plus a custom controller for the mixer on the home page. Its review
surfaced most of the generic needs below; Nocturne's own logic stays in its repository (section 4).

Status: `[ ]` to do · `[~]` in progress · `[x]` done

---

## 1. Before cloning

### [x] Automated tests
The framework holds a lot of logic that has only been checked by hand: routing with language
prefixes, stateless CSRF, the blog compiler and its validation rules, front matter inheritance,
translated posts, pagination, SEO / hreflang, translations, the deploy command.

- PHPUnit, driving the app through `Kernel::handle(Request::create(...))` (no web server needed)
- Fixture content (`tests/fixtures/content/blog/...`) covering every blog rule and error message
- One test per behaviour: language redirects, hidden untranslated posts, pagination edges,
  `/blog/page/1` redirect, 404s, CSRF accept/reject, ETag/304, Host-header independence,
  hreflang/sitemap/feed per language, Datastar partials (SSE output), asset serving
- PHPStan (high level); the code is already annotated
- GitHub Actions: tests + PHPStan + `npm run build` on every push

Why first: Starlite is meant to be reused; tests are what make changing it safe.

Done: 133 tests (framework suite in the package's `tests/`, app suite in the skeleton's `tests/`), each checked by
deliberately breaking the behaviour it covers; PHPStan level 8 with no errors (level 9's strict
`mixed` rules mostly flag SPL iterators and console options); CI in `.github/workflows/ci.yml`.
See README "Testing".

### [x] Extension points
A site could add routes and controllers, but some things could only be changed by editing the
framework, which would have made later Starlite updates conflict-prone:

- **Console commands**: auto-discover commands in `src/Command/` (today `bin/console` lists them by hand)
- **`config/bootstrap.php`**: a hook that receives the kernel, to register the app's Twig extensions,
  globals, functions and services
- **Deploy steps**: let the app add steps to `bin/console deploy` (e.g. "build audio" before "refresh
  Opcache"), configured rather than hardcoded

Rule of thumb for a site: the framework (`vendor/starlite/framework`) is never edited; everything
app-specific lives in `src/`, `config/`, `templates/`, `resources/`, `content/`, `translations/`.

Done: `src/Command/` auto-discovery (`AppCommand` with a lazily booted kernel), `config/bootstrap.php`,
a PSR-11 container (`$app->container`, `$this->get()`), named deploy steps (`addDeployStep()`,
`--skip`, `--list-steps`), and Starlite's Vite logic moved into a framework plugin
(`resources/vite/starlite.js` in the package) so `vite.config.js` stays app-only. See README "Building a site on Starlite".

---

### [x] Separate framework package
The framework moved out of `lib/` into its own repository (lab909/starlite-framework, with its
history), installed by sites as a Composer VCS package. Maintainers clone it into the gitignored
`packages/starlite`, which a path repository prefers over GitHub. Framework updates reach sites as
`composer update starlite/framework` instead of git merges.

### [x] Documentation site
VitePress site in its own repository (lab909/starlite-framework-docs), versioned under `/1.x/`:
introduction, getting started, basics, content, features, extending, deployment, testing, security
and a reference section. Published to GitHub Pages by the docs repository's own workflow.
Keep it in step with the code: maintainers clone it into the gitignored `docs/` and update it with each change.

---

## 2. Generic features for small apps

### [x] Page-specific JavaScript and browser state
- **Several Vite entry points**: one bundle per page or feature (`resources/js/pages/*.js`), included
  through a Twig block, so an app's heavy JS never loads on blog pages (today there's one `app.js`)
- **Documented pattern "JS module + Datastar signals"**: Datastar drives the UI; a plain JS module
  does the heavy work (audio, canvas, maps…) and reads/writes signals
- **Signals persisted in `localStorage`**: settings, favourites, last state. Datastar v1 appears to
  ship persistence only in its Pro version, so this needs a small helper (verify first)
- **Public config for JS**: pass config values to the browser through `<script type="application/json">`
  from an explicit allowlist only, so secrets can't leak into the frontend by accident

Done: every `resources/js/pages/*.js` is an entry point (no config change), added by a page with
`{% set page_scripts = [...] %}`; the Datastar client moved into the framework (alias `'datastar'`,
one shared instance across bundles); framework helpers `ready`, `persist()` and `publicConfig()`
(alias `'starlite'`); `public` allowlist in `config/app.php` printed by `{{ public_config() }}`,
refused if a value contains APP_SECRET. Verified: Datastar v1.0.2 has no persistence, and modules
run before Datastar applies `data-signals` (hence `ready`). Demo: a Web Audio tone on the home page.
Docs: "JavaScript & Datastar".

### [x] Dark / light / system theme
Follow `prefers-color-scheme` with a user override, applied before first paint by a tiny inline
script (no flash). Colour tokens in Tailwind's `@theme`; `dark:` variant driven by a `data-theme`
attribute.

Done: `{{ theme_script() }}` first in `<head>` (framework, `Starlite\Theme`, with `Theme::hash()` for
the CSP); `theme()` in the 'starlite' module (saves the `_theme` signal, follows system changes);
colour tokens with `light-dark()` in `resources/css/app.css`, so the system setting works even without
JavaScript; a radio-group toggle in the header; templates converted to tokens. Also: `cache:clear`
now rebuilds the normal Composer autoloader after a deploy. Docs: "Dark & light theme".

### [x] Self-hosted fonts and icons
Fonts bundled through Vite (e.g. `@fontsource`) instead of Google Fonts: no third-party requests
(privacy/GDPR), works offline, compatible with a strict CSP. Icon fonts (Material Symbols is ~3 MB)
subset to the icons actually used.

Done: Inter from Fontsource bundled by Vite (hashed files in public/build, only the scripts a page
uses are downloaded) and preloaded with the new `{{ vite_preload() }}`; icons through the Iconify
Tailwind plugin (`icon-[lucide--sun]`, only used icons are built, any Iconify set incl. Material
Symbols) plus custom SVGs in `resources/icons/` (`icon-[app--name]`; Vite restarts when they change).
Fixed on the way: Vite's `base` is now `/build/` for builds, so files referenced from CSS resolve.
Docs: "Fonts & icons".

### [ ] UI building blocks
Accessible Twig macros wired to Datastar: dialog / bottom sheet (native `<dialog>`), toast
(`aria-live`; a `toast()` helper a controller can trigger over SSE), keyboard-accessible slider
(arrows, Page Up/Down, Home/End, 44 px hit area). Layout basics: skip link, visible focus styles,
`prefers-reduced-motion` handling.

### [ ] Content Security Policy, cache-friendly
The main missing security header. Use **hashes** of the few inline scripts (the theme script, `Theme::hash()`;
the JSON config is a data block, which CSP doesn't apply to), computed at build time, not per-request nonces: a nonce would make every response
different and break ETags, public caching and the static export. Datastar needs `'unsafe-eval'`;
the rest stays locked down (`default-src 'self'`, `object-src 'none'`, `base-uri 'self'`,
`frame-ancestors 'self'`, `img-src 'self' data:` for the icon masks; verify in a browser), with `media-src` / `worker-src` / CDN hosts from config.

### [ ] Data collections
Structured content beyond blog posts (catalogues, FAQs, team, products…): `content/<collection>/`
with YAML or Markdown front matter, compiled into `var/cache`, validated loudly, translatable like
posts, usable from Twig and exportable as JSON for JS modules.

### [ ] Markdown content pages
`content/pages/<slug>/index.md` (+ `index.<code>.md`, images) for About, Privacy, Imprint, credits…,
reusing the blog machinery (front matter, assets, translations, SEO, sitemap).

### [ ] Small helpers
- [x] `$this->json($data)` in controllers for app data endpoints
- `KernelTestCase` skips Vite when there's no manifest (or fails with a clear message): today a
  new site running `composer test` before the first `npm run build` gets 11 failures that only say
  "Something went wrong." (CI is fine because it builds first)
- `MEDIA_URL` setting so post images and other large files can be served from a CDN, with long cache
  headers by default

### [ ] Optional PWA support
Web app manifest generated from config (name, colours, icons) and an optional service worker
(e.g. `vite-plugin-pwa`), switched on per project.

### [ ] Frontend tests
Vitest for JS modules; Playwright (Chromium is already in the DDEV container) for real user flows.
Wired into the same CI as the PHP tests.

---

## 3. Nice to have

- [ ] **Translate the site name and description** (`config/app.php` → `site`), the last untranslated strings
- [ ] **Contact form**: `symfony/mailer`, validation, database-free spam protection (honeypot +
      minimum fill time, optional rate limit by IP in a file cache), Datastar for a no-reload form
- [ ] **Logging**: Monolog instead of bare `error_log()`, optional e-mail alerts in production
- [ ] **Static export**: render every GET page to HTML at deploy time, served by nginx/Apache with
      `try_files`; everything is ready for it (path-based URLs, no sessions, identical pages for all)
- [ ] **Responsive images**: resized WebP/AVIF variants of post images with `srcset`, built at deploy time

---

## 4. For the Nocturne repository (not Starlite)

Kept here only as a reminder of what the Nocturne site will add on top of Starlite:

- Spike first: background playback with the screen locked (iOS suspends Web Audio; `<audio loop>`
  leaves gaps) and gapless mixing; Media Session API for lock-screen controls. Test on real phones.
- Audio engine (layers, gains, fades, variant crossfades, previews), mixer UI (1a dense vs. 1b
  spacious), saving / My mixes in `localStorage`, share links
- `audio:build` command (ffmpeg: loudness normalisation, seamless loops, Opus + AAC, preview clips),
  registered through Starlite's deploy steps; audio from a CDN via `MEDIA_URL`
- Sound catalogue as a data collection; one SEO page per sound (`/sounds/rain`); sound credits page
- Open questions: mixer direction, layers at once, music source and licensing, launch languages,
  analytics, anything server-side

---

## Suggested order

1. ~~Tests + PHPStan, extension points, docs, separate package~~ — done
2. ~~Page-specific JS + Datastar pattern, theme, fonts/icons~~, CSP (section 2): what an app like
   Nocturne needs on day one
3. Data collections, content pages, UI building blocks, helpers, PWA, frontend tests
4. Create the Nocturne site from the skeleton; section 3 items as projects need them

---

## Housekeeping when starting a site

- [ ] Replace the Datastar demo page and the sample blog posts
- [ ] Real site name, description, default share image, author (`config/app.php`)
- [ ] Languages for the project (`language`, `languages`) and `translations/*.php`
- [ ] Production server: `.env` (`APP_URL`, `APP_SECRET`, `APP_OPCACHE`…), see README "Production setups"
- [ ] Commit the site's own `composer.lock` (remove it from `.gitignore`); update the framework with `composer update starlite/framework`
