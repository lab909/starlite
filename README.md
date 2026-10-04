# Starlite

A tiny, database-free PHP micro framework for static-like dynamic sites: **Datastar** for
reactivity, **Symfony Routing**, **Twig**, a **Markdown blog** with translations, **SEO** (Open Graph,
JSON-LD, hreflang, sitemap, feeds) and **Vite + Tailwind CSS**. Requires PHP 8.4.

Everything a request needs is compiled ahead of time into plain PHP files in `var/cache`, which
Opcache keeps in shared memory: no parsing, no database queries, no file scanning per request.

**📖 Documentation: https://lab909.github.io/starlite-framework-docs/** (source in `docs/`)

## Quick start (DDEV)

```sh
git clone --recurse-submodules git@github.com:lab909/starlite-framework.git my-site
cd my-site
cp .env.example .env         # set APP_SECRET (openssl rand -hex 32), APP_URL and APP_DEBUG=1
ddev start
ddev composer install
ddev npm install
ddev npm run dev             # Vite + Tailwind with hot reload
```

Open `https://<project>.ddev.site`. See [Installation](https://lab909.github.io/starlite-framework-docs/1.x/getting-started/installation)
for the details and for setups without DDEV.

## Building a site on Starlite

A new site is a clone of this repository that **never edits `lib/`**: routes in
`config/routes.php`, services and deploy steps in `config/bootstrap.php`, code in `src/`, templates
in `templates/`, posts in `content/`. Keep Starlite as a git remote to pull framework updates.
See [Building a site on Starlite](https://lab909.github.io/starlite-framework-docs/1.x/extending/).

## Common commands

```sh
ddev composer test           # PHPUnit: framework and app suites
ddev composer analyse        # PHPStan, level 8
ddev console deploy          # production build + Opcache refresh
ddev console cache:clear     # back to development after a deploy
```

## Repository layout

```
lib/          the framework (starlite/framework, a Composer path package): never edited by sites
config/       app settings, bootstrap hook, routes
src/          the app (App\): controllers, commands
templates/    Twig views          content/     blog posts      translations/  UI texts
resources/    CSS, JS, Datastar   public/      web root        tests/         app tests
docs/         documentation (git submodule, VitePress)
```

## Documentation

The docs live in their own repository, included here as the `docs/` submodule:

```sh
git submodule update --init                       # if you cloned without --recurse-submodules
cd docs && npm install && npm run dev             # https://<project>.ddev.site:5174
```

Update the docs together with the code they describe, then commit the new submodule pointer here.

## Roadmap

See [PLAN.md](PLAN.md).
