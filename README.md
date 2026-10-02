# Starlite

A tiny, database-free PHP micro framework for static-like dynamic sites:
**Datastar** (PHP SDK) for reactivity, **Symfony Routing**, **Twig**, a **Markdown** blog,
and **Vite + Tailwind CSS** for assets.

Everything a request needs is compiled ahead of time into plain PHP files in `var/cache`
(routes, Twig templates, blog posts, the Vite manifest). Opcache keeps those in shared memory,
so a production request does no parsing, no database queries and no file scanning.

The framework and the app are kept apart, the same way Laravel and Symfony split
`laravel/framework` from the app skeleton:

```
lib/                  Starlite core: the `starlite/framework` Composer package (namespace Starlite\)
  src/                Kernel, Router, Controller, Datastar Twig extension, Vite, Blog, console commands
src/                  the app (namespace App\): controllers and other app classes
config/app.php        secret + debug flag (from the environment / .env)
config/routes.php     routes → controllers
content/blog/*.md     blog posts (YAML front matter + Markdown)
templates/            Twig views; _partials/ are rendered by Datastar requests
resources/            Vite entry (js/app.js), Tailwind (css/app.css), vendored Datastar client
bin/console           deploy, cache:clear
public/               web root (index.php + build/ from Vite)
var/cache/            compiled caches (safe to delete)
```

`lib/` is installed through a Composer [path repository](https://getcomposer.org/doc/05-repositories.md#path),
symlinked to `vendor/starlite/framework`, so edits take effect immediately. It declares its own
dependencies in `lib/composer.json`; the app's `composer.json` only requires `starlite/framework`.
Core code must never import `App\` classes. To reuse Starlite elsewhere, move `lib/` to its own
repository and point Composer at that instead.

## Local development (DDEV)

```sh
cp .env.example .env         # then set APP_SECRET (openssl rand -hex 32) and APP_DEBUG=1
ddev restart                 # exposes the Vite port
ddev composer install
ddev npm install
ddev npm run dev             # Vite + Tailwind with hot reload on https://starlite-framework.ddev.site:5173
```

Open https://starlite-framework.ddev.site. Editing a template or a post reloads the page.

## Environment

Settings are read from the environment, with a gitignored `.env` file in the project root as the
fallback (loaded by `symfony/dotenv` in `Kernel::loadEnv()`, for both web requests and `bin/console`).
Variables the server already sets (DDEV `web_environment`, PHP-FPM `env[...]`, nginx
`fastcgi_param`) win over `.env`. `.env.example` lists every variable and is the only one committed.

With `APP_DEBUG=1` nothing is cached, drafts are visible and `var/vite.hot` (written while
`npm run dev` runs) points pages at the dev server. Without it, `npm run build` output is used.
If Vite was killed hard and pages still point at port 5173, delete `var/vite.hot`.

## Routing

`config/routes.php` uses Symfony Routing under the hood, so placeholders, requirements and named
routes work as in Symfony. A handler is a `[Controller::class, 'method']` pair, an invokable
controller class, or a closure; placeholders arrive as named arguments:

```php
$app->get('/blog/{slug}', [BlogController::class, 'show'], 'blog_post', ['slug' => '[a-z0-9-]+']);
$app->post('/clock', ClockController::class, 'clock');
$app->route(['PUT', 'PATCH'], '/items/{id}', [ItemController::class, 'update'], 'item_update', ['id' => '\d+']);
```

Controllers live in `src/Controller` and extend `Starlite\Controller`, which gives them the kernel
(`$this->app`) plus `render()`, `stream()` and `notFound()`. Only the matched controller is instantiated.

```php
final class BlogController extends Controller
{
    public function show(string $slug): ?string
    {
        $post = $this->app->blog->find($slug);

        return $post === null ? $this->notFound() : $this->render('blog/post.twig', ['post' => $post]);
    }
}
```

In Twig: `{{ path('blog_post', {slug: post.slug}) }}`. Non-GET routes require the CSRF token,
which `datastar.post()` / `datastar.action()` add automatically. Unknown paths get a 404,
wrong methods a 405 (both render `templates/_error.twig`). GET pages send an ETag, so repeat
visits are answered with a 304.

## Blog

Each `content/blog/*.md` file is a post; the file name minus an optional `YYYY-MM-DD-` prefix is
the slug.

```markdown
---
title: Hello, Starlite      # required
date: 2026-09-20           # required
summary: Teaser text       # optional, defaults to the first paragraph
tags: [php, datastar]
slug: custom-slug          # optional
draft: true                # only shown with APP_DEBUG=1
---
Markdown (GitHub-flavoured: tables, task lists, strikethrough, autolinks).
```

Raw HTML in posts is escaped and `javascript:` links are removed, so content files cannot inject
scripts. Headings get anchor links; external links open in a new tab with `noopener noreferrer`.
The blog list at `/blog` has live search and tag filters through Datastar
(`templates/_partials/blog-results.twig`).

In production, posts are parsed once into `var/cache/blog.php`. Run `bin/console deploy`
(or `cache:clear`) after publishing.

## Datastar (Craft plugin → Starlite)

| Craft Datastar plugin                         | Starlite                                        |
|-----------------------------------------------|-------------------------------------------------|
| `{{ datastar.get('_partials/x', {id: 1}) }}`  | same (also `post`, `put`, `patch`, `delete`)    |
| `{% patchelements %}…{% endpatchelements %}`  | `{% apply patch_elements %}…{% endapply %}`     |
| `{% patchelements with {mode: 'append'} %}`   | `{% apply patch_elements({mode: 'append'}) %}`  |
| `{% patchsignals {a: 1} %}`                   | `{% do patch_signals({a: 1}) %}`                |
| `{% removeelements '#x' %}`                   | `{% do remove_elements('#x') %}`                |
| `{% executescript %}…{% endexecutescript %}`  | `{% do execute_script('…') %}`                  |
| `{% location '/x' %}`                         | `{% do location('/x') %}`                       |
| `signals` variable                            | same                                            |
| custom controller + `Sse` trait               | controller calling `$this->stream(...)`         |
| `datastar.runAction()`                        | write a route instead                           |

Templates that queue no events are auto-patched, as in the plugin. The variables passed to
`datastar.get(...)` are signed (HMAC with `APP_SECRET`) so they cannot be tampered with, but they
are **readable** in the page source: never pass secrets through them.

The Datastar client (v1.0.2) is vendored in `resources/js/vendor/datastar.js` and bundled by Vite.

## Deploying

```sh
npm ci && npm run build      # or pass --assets to deploy
composer install --no-dev --optimize-autoloader
php bin/console deploy      # APP_SECRET from .env or the environment
```

`bin/console deploy` (in DDEV: `ddev console deploy`):

1. runs `composer dump-autoload --optimize --classmap-authoritative`
2. clears `var/cache`, then compiles the routes, every Twig template, the blog posts and the Vite manifest
3. writes the list of PHP files to precompile (`var/cache/opcache-files.php`)
4. sends a signed POST to `/_opcache/warm` so **PHP-FPM** (not the CLI, which has its own Opcache)
   recompiles all of them

Options: `--url` (default `http://127.0.0.1`, or `APP_WARM_URL`) and `--host` to reach PHP-FPM on
the server, `--assets`, `--no-dev`, `--skip-composer`, `--skip-opcache`.

The warm-up endpoint only accepts requests signed with `APP_SECRET` and less than 60 seconds old,
and it compiles only the file list on the server, never paths from the request.

Note: after `deploy` the autoloader is authoritative, so new classes in `src/` are not found
until you run `composer dump-autoload` (or deploy again). Do that when you go back to local development.

### Production PHP and nginx

```ini
; php.ini: files only change on deploy, so skip the per-request stat calls
opcache.enable=1
opcache.validate_timestamps=0
opcache.memory_consumption=128
opcache.max_accelerated_files=20000
opcache.interned_strings_buffer=16
```

```nginx
root /var/www/starlite/public;
location /build/ { expires 1y; add_header Cache-Control "public, immutable"; }
location / { try_files $uri /index.php?$query_string; }
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param APP_SECRET "…";    # or set it in the FPM pool: env[APP_SECRET] = …
    fastcgi_buffering off;           # let SSE events stream immediately
    fastcgi_pass unix:/run/php/php-fpm.sock;
}
```

`var/cache` must be writable by the deploy user and readable by PHP-FPM. If PHP-FPM ever finds a
cache file missing, it rebuilds it on the fly, so it then needs write access too.

## Security notes

- Secrets only come from the environment or `.env` (outside the `public/` web root, gitignored); `config/app.php` refuses to boot without `APP_SECRET`.
- Vite only inlines variables prefixed `VITE_PUBLIC_` into the bundle, so server env vars never reach the frontend.
- `.env`, `.env.*` (except `.env.example`), `var/cache`, `public/build` and `node_modules` are gitignored.
- Responses send `X-Content-Type-Options`, `Referrer-Policy` and `X-Frame-Options` headers.
