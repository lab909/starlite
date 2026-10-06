# Starlite

A tiny, database-free PHP micro framework for static-like dynamic sites: **Datastar** for
reactivity, **Symfony Routing**, **Twig**, a **Markdown blog** with translations, **SEO** (Open Graph,
JSON-LD, hreflang, sitemap, feeds) and **Vite + Tailwind CSS**. Requires PHP 8.4.

Everything a request needs is compiled ahead of time into plain PHP files in `var/cache`, which
Opcache keeps in shared memory: no parsing, no database queries, no file scanning per request.

Starlite is freely inspired by [Craft CMS](https://craftcms.com) (Twig-first templates, content you
query rather than receive, image transforms) and [Datastar](https://data-star.dev) (hypermedia: the
server renders HTML, the browser stays light).

**This repository is the site skeleton: start every new site from it.** The framework itself is the
[`starlite/framework`](https://github.com/lab909/starlite-framework) package, installed by Composer.

📖 **Documentation: https://lab909.github.io/starlite-framework-docs/**

## Create a site

On GitHub, click **Use this template** to create your site's repository, then:

```sh
git clone git@github.com:you/my-site.git && cd my-site
cp .env.example .env         # set APP_SECRET (openssl rand -hex 32), APP_URL and APP_DEBUG=1
ddev start
ddev composer update         # installs starlite/framework from GitHub
ddev npm install
ddev npm run dev             # Vite + Tailwind with hot reload
```

Then remove `/composer.lock` from `.gitignore` and commit your lock file. See
[Installation](https://lab909.github.io/starlite-framework-docs/1.x/getting-started/installation)
for the details and for setups without DDEV.

## Your site, Starlite's framework

Everything in this repository is yours to change: routes in `config/routes.php`, services and
deploy steps in `config/bootstrap.php`, code in `src/`, templates in `templates/`, posts in
`content/`. The framework lives in `vendor/starlite/framework` and is never edited; update it with:

```sh
ddev composer update starlite/framework
```

See [Building a site on Starlite](https://lab909.github.io/starlite-framework-docs/1.x/extending/).

## Common commands

```sh
ddev composer test           # PHPUnit (your app's tests)
ddev composer analyse        # PHPStan, level 8
ddev exec CHROMIUM_PATH=/usr/bin/chromium npm run test:e2e   # the site in a real browser (after npm run build)
ddev console deploy          # production build + Opcache refresh
ddev console cache:clear     # back to development after a deploy
```

## Licenses

The site's built files include third-party code: the Datastar client (MIT), Lucide icons (ISC/MIT),
the Inter font (SIL Open Font License) and Tailwind CSS (MIT). They carry short notices, and
`public/third-party-licenses.txt` has the full texts. If you change fonts, icons or scripts, update
that file too (`tests/LicensesTest.php` checks it).

## Contributing to Starlite

Maintainers work on the skeleton, the framework and the docs side by side: see
[CONTRIBUTING.md](CONTRIBUTING.md). The roadmap is in [PLAN.md](PLAN.md).
