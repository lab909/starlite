# Starlite

A tiny, database-free PHP micro framework for static-like dynamic sites:
**Datastar** (PHP SDK) for reactivity, **Symfony Routing**, **Twig**, a **Markdown** blog,
and **Vite + Tailwind CSS** for assets, with Open Graph, JSON-LD, an Atom feed and a sitemap
built in. Requires PHP 8.4.

Everything a request needs is compiled ahead of time into plain PHP files in `var/cache`
(routes, Twig templates, blog posts, the Vite manifest). Opcache keeps those in shared memory,
so a production request does no parsing, no database queries and no file scanning.

Starlite writes only the glue. The plumbing comes from proven libraries:

| Concern | Library |
|---|---|
| Requests and responses | `symfony/http-foundation` |
| Routing | `symfony/routing` (compiled matcher and generator) |
| CSRF protection | `symfony/security-csrf` (`SameOriginCsrfTokenManager`, stateless) |
| Error handling | `symfony/error-handler` |
| Console, `.env` | `symfony/console`, `symfony/dotenv` |
| Templates | `twig/twig` |
| Markdown | `league/commonmark` |
| JSON-LD structured data | `spatie/schema-org` |
| Reactivity | `starfederation/datastar-php` + the Datastar client |
| Assets | Vite + Tailwind CSS |
| Opcache refresh on deploy | [cachetool](https://github.com/gordalina/cachetool) |

Starlite's own code: the kernel that ties these together, the Craft-style Datastar Twig
extension, the Vite manifest helper and the blog compile step.

The framework and the app are kept apart, the same way Laravel and Symfony split
`laravel/framework` from the app skeleton:

```
lib/                  Starlite core: the `starlite/framework` Composer package (namespace Starlite\)
  src/                Kernel, Router, Controller, Datastar Twig extension, Vite, Blog, Seo, console commands
src/                  the app (namespace App\): controllers and other app classes
config/app.php        secret, debug flag, APP_URL, site-wide SEO defaults
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
cp .env.example .env         # then set APP_SECRET (openssl rand -hex 32), APP_URL=https://starlite-framework.ddev.site and APP_DEBUG=1
ddev restart                 # exposes the Vite port, installs cachetool (.ddev/web-build)
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

With `APP_DEBUG=1` nothing is cached, drafts are visible, errors show Symfony's exception page and `var/vite.hot` (written while
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
(`$this->app`) plus `request()`, `render()`, `stream()` and `notFound()`. An action returns an HTML
string or any Symfony `Response` (JSON, redirects, files…). Only the matched controller is instantiated.

```php
final class BlogController extends Controller
{
    public function show(string $slug): string|Response
    {
        $post = $this->app->blog->find($slug);

        return $post === null ? $this->notFound() : $this->render('blog/post.twig', ['post' => $post]);
    }
}
```

In Twig: `{{ path('blog_post', {slug: post.slug}) }}`. Unknown paths get a 404, wrong methods a
405, and in production any exception is logged and becomes a 500 (all render `templates/_error.twig`).

`Kernel::handle(Request): Response` has no side effects, so the whole app can be exercised in
tests without a web server: `$app->handle(Request::create('/blog'))`.

### CSRF and caching

CSRF protection is stateless: for every non-GET route, the browser's `Sec-Fetch-Site` (or
`Origin`/`Referer`) header must show the request came from this site, otherwise it gets a 403.
There is no token, no cookie and no session. Every browser sends these headers on `fetch()`, which is
how Datastar makes its requests. Behind a reverse proxy, set `APP_TRUSTED_PROXIES` so the origin
check sees the public host and scheme.

Because nothing is per-visitor, every page is identical for everyone: GET responses are sent
`Cache-Control: public, no-cache` with an ETag, so browsers and proxies can store them and
revalidate with a cheap 304.

## Blog

Each `content/blog/*.md` file is a post; the file name minus an optional `YYYY-MM-DD-` prefix is
the slug.

```markdown
---
title: Hello, Starlite      # required
date: 2026-09-20           # required
updated: 2026-09-25        # optional, last significant change (feed, sitemap, dateModified)
image: /images/hello.jpg   # optional share image: a path in public/ or an https:// URL
summary: Teaser text       # optional, defaults to the first paragraph; also the meta description
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

## SEO

Every page gets a `<title>`, meta description, canonical link, Open Graph and Twitter card tags,
and optional JSON-LD blocks. They're all printed once by `{{ seo_tags() }}` in `_layout.twig`.

- **Blog posts** are mapped from front matter automatically (`Starlite\Blog\PostSeo`): title,
  summary, image, `og:type=article` with published/modified dates and tags, and a `BlogPosting`
  JSON-LD block. Drafts get `noindex`.
- **Error pages** get `noindex`.
- **Site defaults** (name, description, locale, default image, author) live in `config/app.php`
  under `site`.

Set page metadata from a controller:

```php
$seo = $this->app->seo->title('About')->description('Who we are.')->image('/images/about.jpg');
$seo->schema(Schema::organization()->name('Acme')->url($seo->url('/')));   // spatie/schema-org
```

or from a template, in the `seo` block (it renders before the tags are printed):

```twig
{% block seo %}{% do seo.title('About').description('Who we are.') %}{% endblock %}
```

Other setters: `canonical()`, `type()`, `noindex()`, `article()`. `absolute_url('/path')` is
available in Twig.

All absolute URLs (canonical, `og:url`, `og:image`, sitemap, feed) are built from `APP_URL`,
never from the request's `Host` header. Pages are publicly cacheable, so a forged `Host` must not
end up in a cached page. JSON-LD is encoded so a `</script>` in any value can't break out of the tag.

### Feed, sitemap, robots.txt

The framework ships three controllers, wired in `config/routes.php`:

| URL | Controller | Content |
|---|---|---|
| `/blog/feed.xml` | `Starlite\Blog\FeedController` | Atom feed of the 20 latest published posts (full HTML content), linked from every page's `<head>` |
| `/sitemap.xml` | `Starlite\Seo\SitemapController` | Every static GET page (no placeholders, no file extension) plus every published post with its `lastmod` |
| `/robots.txt` | `Starlite\Seo\RobotsController` | Allows everything and points crawlers at the sitemap |

The feed expects the blog routes to be named `blog` and `blog_post`. Drafts never appear in
either, even with `APP_DEBUG=1`.

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
3. refreshes the web server's Opcache, so the new code is used even with
   `opcache.validate_timestamps=0`. The CLI has its own Opcache, so this always goes through the
   web server's PHP. How it does that depends on the server, see below.

Options: `--opcache=cachetool|reload|none` (default `APP_OPCACHE`, else `cachetool`), `--fcgi`,
`--cachetool`, `--reload-cmd`, `--assets`, `--no-dev`, `--skip-composer`, `--skip-opcache`
(same as `--opcache=none`). Set the Opcache variables once per server in its `.env`, then plain
`php bin/console deploy` does the right thing everywhere.

### Production setups

| Server | `.env` on that server | What deploy does |
|---|---|---|
| nginx + PHP-FPM | `APP_OPCACHE=cachetool`<br>`APP_FPM_SOCKET=/run/php/php8.4-fpm.sock` | Invalidates and precompiles every file inside FPM: no slow first requests |
| Apache + PHP-FPM (`mod_proxy_fcgi`) | same as nginx | same as nginx |
| nginx/Apache + PHP-FPM, without cachetool | `APP_OPCACHE=reload`<br>`APP_OPCACHE_RELOAD_CMD="sudo systemctl reload php8.4-fpm"` | Graceful FPM reload empties Opcache; files recompile on first use |
| Apache + mod_php | `APP_OPCACHE=reload`<br>`APP_OPCACHE_RELOAD_CMD="sudo apachectl graceful"` | Graceful Apache restart empties Opcache |
| Shared hosting | `APP_OPCACHE=none` | Nothing. Hosts normally keep `validate_timestamps=1`, so changes are picked up anyway |

Socket names differ per distribution: check `listen =` in the FPM pool config
(e.g. `/etc/php/8.4/fpm/pool.d/www.conf`).

**cachetool mode.** Install the same pinned, checksum-verified phar DDEV uses
(`.ddev/web-build/Dockerfile.cachetool`):

```sh
curl -fsSL -o /usr/local/bin/cachetool https://github.com/gordalina/cachetool/releases/download/10.0.0/cachetool.phar
echo "cbe90e7acdde7beafe26b592a753c2b923a99d2033e073dc55e42fba2883bd1d  /usr/local/bin/cachetool" | sha256sum -c -
chmod 755 /usr/local/bin/cachetool
```

The deploy user needs access to the FPM socket, which is usually owned by `www-data` with mode
`0660`: add the deploy user to that group (`sudo usermod -aG www-data deploy`), or run the deploy as
`www-data`. cachetool is used as a standalone phar rather than a Composer dependency: its Composer
package still requires Symfony 6, which conflicts with the framework's Symfony 7 components.

**reload mode.** Give the deploy user passwordless sudo for that one command only, e.g. in
`/etc/sudoers.d/starlite`:

```
deploy ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm
```

A graceful reload lets running requests finish, so there is no downtime. The reload command is
read from the server's own configuration and run as given, so keep `.env` writable only by the
deploy user.

Note: after `deploy` the autoloader is authoritative, so new classes in `src/` are not found
until you run `composer dump-autoload` (or deploy again). Do that when you go back to local development.

### Production PHP, nginx and Apache

```ini
; php.ini: files only change on deploy, so skip the per-request stat calls
opcache.enable=1
opcache.validate_timestamps=0
opcache.memory_consumption=128
opcache.max_accelerated_files=20000
opcache.interned_strings_buffer=16
display_errors=Off
log_errors=On
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

Apache (`a2enmod proxy_fcgi rewrite headers expires`), with PHP-FPM:

```apache
DocumentRoot /var/www/starlite/public
<Directory /var/www/starlite/public>
    AllowOverride None
    Require all granted
    FallbackResource /index.php
</Directory>
<FilesMatch "\.php$">
    SetHandler "proxy:unix:/run/php/php-fpm.sock|fcgi://localhost"
</FilesMatch>
# Let SSE events stream immediately (Apache does not allow comments after a directive)
<Proxy "fcgi://localhost">
    ProxySet flushpackets=on
</Proxy>
<Location /build/>
    Header set Cache-Control "public, max-age=31536000, immutable"
</Location>
```

With mod_php instead of PHP-FPM, drop the `FilesMatch` and `Proxy` blocks. Either way, set
`APP_SECRET` in the server's `.env` (or `SetEnv APP_SECRET …` in the vhost, readable only by root).

`var/cache` must be writable by the deploy user and readable by PHP. If PHP-FPM ever finds a
cache file missing, it rebuilds it on the fly, so it then needs write access too. The same goes
for Apache with mod_php.

## Security notes

- Absolute URLs come from `APP_URL` only, so a forged `Host` header can't poison cached pages.
- Secrets only come from the environment or `.env` (outside the `public/` web root, gitignored); `config/app.php` refuses to boot without `APP_SECRET`.
- Vite only inlines variables prefixed `VITE_PUBLIC_` into the bundle, so server env vars never reach the frontend.
- `.env`, `.env.*` (except `.env.example`), `var/cache`, `public/build` and `node_modules` are gitignored.
- Responses send `X-Content-Type-Options`, `Referrer-Policy` and `X-Frame-Options` headers.
- Non-GET requests from other sites are rejected (stateless origin check, see *CSRF and caching*).
- Production errors are logged, never shown: visitors get a generic 500 page.
- There is no HTTP endpoint for deployment tasks; cachetool reaches PHP-FPM through its local socket.
