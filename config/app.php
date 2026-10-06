<?php

// Secrets come from the environment or the gitignored .env file, never from the repo or the frontend bundle.
// Generate a secret with `openssl rand -hex 32`. See .env.example.
$url = rtrim((string) getenv('APP_URL'), '/');
if (!preg_match('#^https?://[^/\s]+(/\S*)?$#', $url)) {
    throw new RuntimeException('APP_URL must be the public base URL, e.g. https://example.com');
}

return [
    'secret' => getenv('APP_SECRET') ?: throw new RuntimeException('APP_SECRET is not set.'),
    'debug' => getenv('APP_DEBUG') === '1',
    // Public base URL: canonical links, Open Graph, sitemap and feed. Never derived from the Host header.
    'url' => $url,
    // Where post and page files (images, PDFs, videos) and video posters are served from: '' for this
    // site (/media/…), or a CDN that pulls them from it, e.g. https://cdn.example.com (MEDIA_URL).
    'media_url' => (string) getenv('MEDIA_URL'),
    // Responsive images: post and page images get AVIF (where the server's GD or Imagick can write it)
    // and WebP versions at these widths, and every published image loses its metadata (GPS position…).
    // `sizes` says how wide images are shown: the content column here (max-w-3xl, 48rem).
    'images' => [
        'widths' => [480, 960, 1440],
        'sizes' => '(min-width: 48rem) 48rem, 100vw',
        // Presets for templates, like Craft's image transforms: {{ image(url, alt, {preset: 'card'}) }}.
        // Every option is optional: widths and sizes default to the ones above; with a ratio, mode
        // (crop, fit, letterbox, stretch) defaults to crop, position to center, background to transparent.
        'presets' => [
            // 'hero' => ['widths' => [1280, 1920, 2560], 'sizes' => '100vw'],
            // 'card' => ['widths' => [400, 800], 'ratio' => '16:9', 'sizes' => '(min-width: 48rem) 24rem, 100vw'],
            // 'avatar' => ['widths' => [96, 192], 'ratio' => '1:1', 'position' => 'top', 'sizes' => '6rem'],
        ],
    ],
    // Sending email (forms): a Symfony Mailer DSN, e.g. smtp://user:pass@smtp.example.com:587, and the
    // address messages come from (one of your own domain). In DDEV, Mailpit catches everything.
    'mailer' => [
        'dsn' => getenv('MAILER_DSN') ?: null,
        'from' => getenv('MAILER_FROM') ?: null,
    ],
    // The log: a file per day in var/log/ (app-2026-10-06.log), deleted after `days`. Entries say which
    // page a message comes from (method, path, route), never who was visiting: no IP address, browser
    // or query string. `level`: debug in development, info in production (LOG_LEVEL). `alert_to`: who
    // gets an email when something breaks (LOG_ALERT_TO, sent with the mailer above; once per hour per
    // error). `path`: another file, or a stream like php://stderr for platforms that collect it.
    'log' => [
        'level' => getenv('LOG_LEVEL') ?: null,
        'days' => 14,
        'alert_to' => getenv('LOG_ALERT_TO') ?: null,
    ],
    // Caching pages at a CDN (Cloudflare, Bunny…), off until CDN_CACHE=1: the CDN keeps each page for
    // `ttl` seconds and keeps serving it while this server is down (`stale_if_error`). Every route is
    // cached except those in `exclude`, content pages with `cdn: false`, pages with a form, and errors.
    // `ttl` defaults to an hour when `deploy` can purge the CDN (CDN_PURGE), to 5 minutes otherwise.
    // Purge single pages with `bin/console cdn:purge blog/my-post`.
    'cdn' => [
        'enabled' => getenv('CDN_CACHE') === '1',
        'exclude' => ['clock'],
        'purge' => getenv('CDN_PURGE') ?: null,   // cloudflare, bunny or command
        'cloudflare' => ['zone' => getenv('CLOUDFLARE_ZONE_ID') ?: null, 'token' => getenv('CLOUDFLARE_API_TOKEN') ?: null],
        'bunny' => ['pull_zone' => getenv('BUNNY_PULL_ZONE_ID') ?: null, 'key' => getenv('BUNNY_API_KEY') ?: null],
        'command' => getenv('CDN_PURGE_CMD') ?: null,
    ],
    // Comma-separated IPs/CIDRs of reverse proxies whose X-Forwarded-* headers are trusted ('REMOTE_ADDR' = the direct peer).
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) getenv('APP_TRUSTED_PROXIES'))))),
    // Default language: served without a URL prefix (/blog). Every other language in `languages`
    // gets its code as prefix (/it/blog). An Italian-only site: 'language' => 'it' and list only 'it';
    // add 'en' later and English appears under /en/. UI texts live in translations/<code>.php.
    'language' => 'en',
    'languages' => [
        'en' => ['name' => 'English', 'locale' => 'en_US'],
        'it' => ['name' => 'Italiano', 'locale' => 'it_IT'],
    ],
    'blog' => [
        // Posts per page on /blog. Set BLOG_PER_PAGE=1 in .env to try pagination with only a few posts.
        'per_page' => max(1, (int) (getenv('BLOG_PER_PAGE') ?: 20)),
    ],
    // Values page scripts may read (`{{ public_config() }}` in the layout, `publicConfig()` in JS). This is
    // an allowlist: nothing else from this file reaches the browser. Everything here is public, so never
    // put secrets or private URLs in it (a value containing APP_SECRET is refused).
    // E.g. 'public' => ['sounds_url' => getenv('SOUNDS_URL') ?: '/sounds'],
    'public' => [],
    // Content Security Policy: browsers only load scripts, styles, fonts, images, frames… from your own
    // site, plus the hosts listed here per directive. Add what a feature needs, e.g. a video player:
    //   'frame-src' => ['https://www.youtube-nocookie.com'], 'media-src' => ['https://cdn.example.com'],
    // report_only: browsers report violations in the console without blocking (to try a change first).
    'csp' => [
        'enabled' => true,
        'report_only' => false,
        'sources' => [],
    ],
    // Site-wide SEO defaults; pages override them through `seo` (see README, "SEO").
    // Each value is the same in every language, or a map language => value; a language left out of
    // a map uses the default language's (null in a map: none in that language, e.g. no image).
    'site' => [
        'name' => 'Starlite',
        'description' => [
            'en' => 'A database-free PHP micro framework for static-like dynamic sites.',
            'it' => 'Un micro framework PHP senza database per siti dinamici veloci come quelli statici.',
        ],
        'image' => null,   // default share image, e.g. '/images/og-default.png' or ['en' => …, 'it' => …]
        'author' => null,  // used for blog posts and the feed; defaults to the site name
    ],
];
